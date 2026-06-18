<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\ExampleSentence;
use App\Services\TextBlockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupNonWordVocabulary extends Command
{
    protected $signature = "linguacafe:cleanup-non-word-vocabulary
        {--apply : Apply safe cleanup. Without this option the command only reports what would change.}
        {--user-id= : Restrict cleanup to a single user id.}
        {--language= : Restrict cleanup to one language.}
        {--chunk=500 : Number of rows to scan per chunk.}";

    protected $description = "Report invalid vocabulary by reason, then delete safe rows or quarantine rows with user history.";

    public function handle(): int
    {
        $apply = (bool) $this->option("apply");
        $userId = $this->option("user-id");
        $language = $this->option("language");
        $chunkSize = max(1, intval($this->option("chunk") ?: 500));
        $candidates = [];
        $summary = [
            "mode" => $apply ? "apply" : "dry-run",
            "scanned" => 0,
            "invalid" => 0,
            "reason_counts" => [],
            "ambiguous" => 0,
            "would_delete" => 0,
            "would_quarantine" => 0,
            "deleted" => 0,
            "quarantined" => 0,
            "already_ignored" => 0,
            "skipped_ambiguous" => 0,
            "chapters_repaired" => 0,
            "books_recalculated" => 0,
            "candidates" => [],
        ];

        $query = EncounteredWord::query()
            ->select([
                "id",
                "user_id",
                "language",
                "word",
                "stage",
                "translation",
                "reading",
                "base_word",
                "base_word_reading",
                "lemma",
                "kanji",
                "read_count",
                "lookup_count",
                "next_review",
                "added_to_srs",
                "relearning",
            ])
            ->orderBy("id");

        if ($userId !== null && $userId !== "") {
            $query->where("user_id", intval($userId));
        }

        if ($language !== null && $language !== "") {
            $query->where("language", $language);
        }

        $query->chunkById($chunkSize, function ($words) use (&$candidates, &$summary) {
            foreach ($words as $word) {
                $summary["scanned"]++;
                $decision = TextBlockService::classifyVocabularyToken($word->word, $word->language);

                if ($decision["valid"]) {
                    continue;
                }

                $summary["invalid"]++;
                $summary["reason_counts"][$decision["reason"]] =
                    ($summary["reason_counts"][$decision["reason"]] ?? 0) + 1;
                $scopeKey = $word->user_id . ":" . $word->language;
                $candidateKey = $scopeKey . ":" . mb_strtolower($word->word, "UTF-8");

                if (!isset($candidates[$candidateKey])) {
                    $candidates[$candidateKey] = [
                        "token" => $word->word,
                        "user_id" => intval($word->user_id),
                        "language" => $word->language,
                        "reason" => $decision["reason"],
                        "ambiguous" => (bool) $decision["ambiguous"],
                        "rows" => [],
                        "chapter_ids" => [],
                        "book_ids" => [],
                        "example_sentence_records" => 0,
                    ];
                }

                $candidates[$candidateKey]["rows"][] = $word;
            }
        }, "id");

        $this->addAssociations($candidates);
        $actions = $this->classifyCleanupActions($candidates, $summary);

        if ($apply && count($actions["actionable_ids"])) {
            DB::transaction(function () use (&$summary, $actions) {
                $this->repairChapterMetadata(
                    $actions["actionable_ids"],
                    $actions["actionable_words"],
                    $summary
                );

                if (count($actions["delete_ids"])) {
                    EncounteredWord::query()->whereIn("id", $actions["delete_ids"])->delete();
                    $summary["deleted"] = count($actions["delete_ids"]);
                }

                if (count($actions["quarantine_ids"])) {
                    EncounteredWord::query()
                        ->whereIn("id", $actions["quarantine_ids"])
                        ->update([
                            "stage" => 1,
                            "next_review" => null,
                            "relearning" => false,
                        ]);
                    $summary["quarantined"] = count($actions["quarantine_ids"]);
                }
            });
        }

        ksort($summary["reason_counts"]);
        Log::info("Non-word vocabulary cleanup completed.", array_diff_key($summary, ["candidates" => true]));
        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            $this->warn("Dry-run only. Review candidates before re-running with --apply.");
        }

        return self::SUCCESS;
    }

    private function addAssociations(array &$candidates): void
    {
        $candidatesByScope = [];

        foreach ($candidates as $candidateKey => $candidate) {
            $scopeKey = $candidate["user_id"] . ":" . $candidate["language"];
            $candidatesByScope[$scopeKey][$candidateKey] = true;
        }

        foreach ($candidatesByScope as $scopeKey => $candidateKeys) {
            [$userId, $language] = explode(":", $scopeKey, 2);
            $scopeCandidates = array_intersect_key($candidates, $candidateKeys);
            $idsToCandidate = [];
            $wordsToCandidate = [];

            foreach ($scopeCandidates as $candidateKey => $candidate) {
                $wordsToCandidate[mb_strtolower($candidate["token"], "UTF-8")] = $candidateKey;
                foreach ($candidate["rows"] as $row) {
                    $idsToCandidate[intval($row->id)] = $candidateKey;
                }
            }

            Chapter::query()
                ->where("user_id", intval($userId))
                ->where("language", $language)
                ->select(["id", "book_id", "unique_words", "unique_word_ids"])
                ->orderBy("id")
                ->chunkById(100, function ($chapters) use (&$candidates, $idsToCandidate, $wordsToCandidate) {
                    foreach ($chapters as $chapter) {
                        $matchedCandidates = [];

                        foreach (json_decode($chapter->unique_word_ids) ?: [] as $wordId) {
                            if (isset($idsToCandidate[intval($wordId)])) {
                                $matchedCandidates[$idsToCandidate[intval($wordId)]] = true;
                            }
                        }

                        foreach (json_decode($chapter->unique_words) ?: [] as $word) {
                            $lowerWord = mb_strtolower((string) $word, "UTF-8");
                            if (isset($wordsToCandidate[$lowerWord])) {
                                $matchedCandidates[$wordsToCandidate[$lowerWord]] = true;
                            }
                        }

                        foreach (array_keys($matchedCandidates) as $candidateKey) {
                            $candidates[$candidateKey]["chapter_ids"][intval($chapter->id)] = true;
                            $candidates[$candidateKey]["book_ids"][intval($chapter->book_id)] = true;
                        }
                    }
                });

            foreach ($scopeCandidates as $candidateKey => $candidate) {
                $rowIds = array_map(fn ($row) => intval($row->id), $candidate["rows"]);
                $candidates[$candidateKey]["example_sentence_records"] = ExampleSentence::query()
                    ->where("user_id", intval($userId))
                    ->where("language", $language)
                    ->where("target_type", "word")
                    ->whereIn("target_id", $rowIds)
                    ->count();
            }
        }
    }

    private function classifyCleanupActions(array $candidates, array &$summary): array
    {
        $deleteIds = [];
        $quarantineIds = [];
        $actionableIds = [];
        $actionableWords = [];

        foreach ($candidates as $candidate) {
            $rowIds = array_map(fn ($row) => intval($row->id), $candidate["rows"]);
            $reviewRecords = count(array_filter($candidate["rows"], function ($row) {
                return intval($row->stage) < 0 || $row->next_review !== null || $row->added_to_srs !== null;
            }));
            $highlightedRecords = count(array_filter(
                $candidate["rows"],
                fn ($row) => intval($row->stage) < 0
            ));
            $hasUserHistory = count(array_filter($candidate["rows"], function ($row) {
                return intval($row->stage) !== 2
                    || trim((string) $row->translation) !== ""
                    || trim((string) $row->reading) !== ""
                    || trim((string) $row->base_word) !== ""
                    || trim((string) $row->base_word_reading) !== ""
                    || trim((string) $row->lemma) !== ""
                    || trim((string) $row->kanji) !== ""
                    || intval($row->read_count) > 0
                    || intval($row->lookup_count) > 0
                    || $row->next_review !== null
                    || $row->added_to_srs !== null
                    || (bool) $row->relearning;
            })) > 0 || $candidate["example_sentence_records"] > 0;
            $manualReview = $candidate["ambiguous"];
            $safeToDelete = !$manualReview && !$hasUserHistory;
            $alreadyIgnored = count(array_filter(
                $candidate["rows"],
                fn ($row) => intval($row->stage) === 1
            ));

            if ($manualReview) {
                $summary["ambiguous"]++;
                $summary["skipped_ambiguous"] += count($rowIds);
            } elseif ($safeToDelete) {
                $summary["would_delete"] += count($rowIds);
                array_push($deleteIds, ...$rowIds);
            } else {
                $idsToQuarantine = array_values(array_filter(
                    $candidate["rows"],
                    fn ($row) => intval($row->stage) !== 1
                ));
                $idsToQuarantine = array_map(fn ($row) => intval($row->id), $idsToQuarantine);
                $summary["already_ignored"] += $alreadyIgnored;
                $summary["would_quarantine"] += count($idsToQuarantine);
                array_push($quarantineIds, ...$idsToQuarantine);
            }

            if (!$manualReview) {
                foreach ($rowIds as $rowId) {
                    $actionableIds[$candidate["user_id"] . ":" . $candidate["language"]][$rowId] = true;
                }
                $actionableWords[$candidate["user_id"] . ":" . $candidate["language"]][
                    mb_strtolower($candidate["token"], "UTF-8")
                ] = true;
            }

            $summary["candidates"][] = [
                "token" => $candidate["token"],
                "user_id" => $candidate["user_id"],
                "language" => $candidate["language"],
                "reason" => $candidate["reason"],
                "encountered_word_ids" => $rowIds,
                "encountered_word_count" => count($rowIds),
                "review_records" => $reviewRecords,
                "highlighted_records" => $highlightedRecords,
                "flashcard_records" => 0,
                "example_sentence_records" => $candidate["example_sentence_records"],
                "chapter_ids" => array_map("intval", array_keys($candidate["chapter_ids"])),
                "book_ids" => array_map("intval", array_keys($candidate["book_ids"])),
                "safe_to_delete" => $safeToDelete,
                "manual_review" => $manualReview,
            ];
        }

        return [
            "delete_ids" => array_values(array_unique($deleteIds)),
            "quarantine_ids" => array_values(array_unique($quarantineIds)),
            "actionable_ids" => $actionableIds,
            "actionable_words" => $actionableWords,
        ];
    }

    private function repairChapterMetadata(array $invalidIdsByScope, array $invalidWordsByScope, array &$summary): void
    {
        $affectedBookIdsByScope = [];

        foreach ($invalidIdsByScope as $scopeKey => $invalidIdMap) {
            [$userId, $language] = explode(":", $scopeKey, 2);
            $invalidIds = array_map("intval", array_keys($invalidIdMap));
            $invalidWords = array_keys($invalidWordsByScope[$scopeKey] ?? []);

            Chapter::query()
                ->where("user_id", intval($userId))
                ->where("language", $language)
                ->select(["id", "book_id", "unique_words", "unique_word_ids", "word_count", "processed_text"])
                ->orderBy("id")
                ->chunkById(100, function ($chapters) use (
                    $scopeKey,
                    $invalidIds,
                    $invalidWords,
                    &$affectedBookIdsByScope,
                    &$summary
                ) {
                    foreach ($chapters as $chapter) {
                        $changed = false;
                        $uniqueWordIds = json_decode($chapter->unique_word_ids) ?: [];
                        $uniqueWords = json_decode($chapter->unique_words) ?: [];
                        $filteredWordIds = array_values(array_filter(
                            $uniqueWordIds,
                            fn ($wordId) => !in_array(intval($wordId), $invalidIds, true)
                        ));
                        $filteredWords = array_values(array_filter($uniqueWords, function ($word) use ($invalidWords) {
                            return !in_array(mb_strtolower((string) $word, "UTF-8"), $invalidWords, true);
                        }));

                        if ($filteredWordIds !== $uniqueWordIds) {
                            $chapter->unique_word_ids = json_encode($filteredWordIds);
                            $changed = true;
                        }

                        if ($filteredWords !== $uniqueWords) {
                            $chapter->unique_words = json_encode($filteredWords);
                            $changed = true;
                        }

                        $processedText = $chapter->getProcessedText();
                        if (count($processedText)) {
                            $wordCount = count(array_filter($processedText, function ($processedWord) use ($chapter) {
                                return TextBlockService::isVocabularyToken(
                                    $processedWord->word ?? $processedWord->w ?? "",
                                    $chapter->language
                                );
                            }));

                            if (intval($chapter->word_count) !== $wordCount) {
                                $chapter->word_count = $wordCount;
                                $changed = true;
                            }
                        }

                        if ($changed) {
                            $chapter->save();
                            $summary["chapters_repaired"]++;
                            $affectedBookIdsByScope[$scopeKey][intval($chapter->book_id)] = true;
                        }
                    }
                });
        }

        foreach ($affectedBookIdsByScope as $scopeKey => $bookIdMap) {
            [$userId] = explode(":", $scopeKey, 2);

            foreach (array_keys($bookIdMap) as $bookId) {
                $wordCount = Chapter::query()
                    ->where("user_id", intval($userId))
                    ->where("book_id", intval($bookId))
                    ->sum("word_count");

                Book::query()
                    ->where("user_id", intval($userId))
                    ->where("id", intval($bookId))
                    ->update(["word_count" => intval($wordCount)]);

                $summary["books_recalculated"]++;
            }
        }
    }
}
