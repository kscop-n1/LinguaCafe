<?php

namespace App\Console\Commands;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Services\TextBlockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillVocabularyMetadata extends Command
{
    protected $signature = "linguacafe:backfill-vocabulary-metadata
        {--apply : Apply unambiguous metadata repairs. Without this option the command is a dry-run.}
        {--user-id= : Restrict backfill to a single user id.}
        {--language= : Restrict backfill to one language.}
        {--chunk=100 : Number of chapters to scan per chunk.}";

    protected $description = "Safely backfill chapter vocabulary and phrase IDs with duplicate-text detection.";

    public function handle(): int
    {
        $apply = (bool) $this->option("apply");
        $chunkSize = max(1, intval($this->option("chunk") ?: 100));
        $summary = [
            "mode" => $apply ? "apply" : "dry-run",
            "chapters_scanned" => 0,
            "chapters_would_change" => 0,
            "chapters_changed" => 0,
            "word_ids_added" => 0,
            "word_ids_removed" => 0,
            "phrase_ids_added" => 0,
            "phrase_ids_removed" => 0,
            "ambiguous_chapters" => 0,
            "unresolved_words" => 0,
            "ambiguities" => [],
            "unresolved" => [],
        ];

        $query = Chapter::query()
            ->where("processing_status", ChapterProcessingStatusEnum::PROCESSED->value)
            ->select([
                "id",
                "user_id",
                "book_id",
                "language",
                "unique_words",
                "unique_word_ids",
                "unique_phrase_ids",
                "processed_text",
            ])
            ->orderBy("id");

        if ($this->option("user-id") !== null && $this->option("user-id") !== "") {
            $query->where("user_id", intval($this->option("user-id")));
        }

        if ($this->option("language") !== null && $this->option("language") !== "") {
            $query->where("language", $this->option("language"));
        }

        $query->chunkById($chunkSize, function ($chapters) use ($apply, &$summary) {
            foreach ($chapters as $chapter) {
                $summary["chapters_scanned"]++;
                $repair = $this->buildRepair($chapter);

                if (count($repair["ambiguities"])) {
                    $summary["ambiguous_chapters"]++;
                    array_push($summary["ambiguities"], ...$repair["ambiguities"]);
                }

                $summary["unresolved_words"] += count($repair["unresolved_words"]);
                foreach ($repair["unresolved_words"] as $word) {
                    $summary["unresolved"][] = [
                        "chapter_id" => intval($chapter->id),
                        "user_id" => intval($chapter->user_id),
                        "language" => $chapter->language,
                        "word" => $word,
                    ];
                }

                if (!$repair["changed"]) {
                    continue;
                }

                $summary["chapters_would_change"]++;
                $summary["word_ids_added"] += $repair["word_ids_added"];
                $summary["word_ids_removed"] += $repair["word_ids_removed"];
                $summary["phrase_ids_added"] += $repair["phrase_ids_added"];
                $summary["phrase_ids_removed"] += $repair["phrase_ids_removed"];

                if (!$apply) {
                    continue;
                }

                DB::transaction(function () use ($chapter, $repair, &$summary) {
                    $lockedChapter = Chapter::query()
                        ->where("id", $chapter->id)
                        ->where("user_id", $chapter->user_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedChapter) {
                        return;
                    }

                    if ($repair["word_ids"] !== null) {
                        $lockedChapter->unique_word_ids = json_encode($repair["word_ids"]);
                    }

                    if ($repair["phrase_ids"] !== null) {
                        $lockedChapter->unique_phrase_ids = json_encode($repair["phrase_ids"]);
                    }

                    $lockedChapter->save();
                    $summary["chapters_changed"]++;
                });
            }
        }, "id");

        Log::info(
            "Vocabulary metadata backfill completed.",
            array_diff_key($summary, ["ambiguities" => true, "unresolved" => true])
        );
        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            $this->warn("Dry-run only. Review ambiguities before re-running with --apply.");
        }

        return self::SUCCESS;
    }

    private function buildRepair(Chapter $chapter): array
    {
        $uniqueWords = array_values(array_unique(array_filter(
            json_decode($chapter->unique_words) ?: [],
            fn ($word) => TextBlockService::isVocabularyToken($word, $chapter->language)
        )));
        $uniqueWordsByLowercase = [];
        foreach ($uniqueWords as $word) {
            $uniqueWordsByLowercase[mb_strtolower($word, "UTF-8")] = true;
        }
        $existingWordIds = array_values(array_unique(array_map(
            "intval",
            json_decode($chapter->unique_word_ids) ?: []
        )));
        $wordRows = EncounteredWord::query()
            ->where("user_id", $chapter->user_id)
            ->where("language", $chapter->language)
            ->whereIn("word", $uniqueWords)
            ->get(["id", "word"])
            ->groupBy(fn ($word) => mb_strtolower($word->word, "UTF-8"));
        $existingRows = EncounteredWord::query()
            ->where("user_id", $chapter->user_id)
            ->where("language", $chapter->language)
            ->whereIn("id", $existingWordIds)
            ->get(["id", "word"])
            ->keyBy("id");
        $resolvedWordIds = [];
        $coveredWords = [];

        foreach ($existingWordIds as $wordId) {
            $row = $existingRows->get($wordId);
            if (!$row || !isset($uniqueWordsByLowercase[mb_strtolower($row->word, "UTF-8")])) {
                continue;
            }

            $resolvedWordIds[] = intval($row->id);
            $coveredWords[mb_strtolower($row->word, "UTF-8")] = true;
        }

        $ambiguities = [];
        $unresolvedWords = [];

        foreach ($uniqueWords as $word) {
            $lowerWord = mb_strtolower($word, "UTF-8");
            if (isset($coveredWords[$lowerWord])) {
                continue;
            }

            $candidates = $wordRows->get($lowerWord, collect());
            if ($candidates->count() === 1) {
                $resolvedWordIds[] = intval($candidates->first()->id);
                continue;
            }

            if ($candidates->count() > 1) {
                $ambiguities[] = [
                    "chapter_id" => intval($chapter->id),
                    "user_id" => intval($chapter->user_id),
                    "language" => $chapter->language,
                    "word" => $word,
                    "candidate_count" => $candidates->count(),
                ];
            } else {
                $unresolvedWords[] = $word;
            }
        }

        $wordIds = null;
        if (!count($ambiguities) && !count($unresolvedWords)) {
            $wordIds = array_values(array_unique($resolvedWordIds));
            sort($wordIds);
        }

        $existingPhraseIds = array_values(array_unique(array_map(
            "intval",
            json_decode($chapter->unique_phrase_ids) ?: []
        )));
        $processedPhraseIds = [];
        foreach ($chapter->getProcessedText() ?: [] as $processedWord) {
            foreach (($processedWord->phrase_ids ?? []) as $phraseId) {
                $processedPhraseIds[intval($phraseId)] = true;
            }
        }
        $candidatePhraseIds = array_values(array_unique(array_merge(
            $existingPhraseIds,
            array_keys($processedPhraseIds)
        )));
        $phraseIds = Phrase::query()
            ->where("user_id", $chapter->user_id)
            ->where("language", $chapter->language)
            ->whereIn("id", $candidatePhraseIds)
            ->pluck("id")
            ->map(fn ($phraseId) => intval($phraseId))
            ->sort()
            ->values()
            ->toArray();

        $currentWordIds = $existingWordIds;
        sort($currentWordIds);
        $currentPhraseIds = $existingPhraseIds;
        sort($currentPhraseIds);
        $wordChanged = $wordIds !== null && (
            $chapter->unique_word_ids === null || $wordIds !== $currentWordIds
        );
        $phraseChanged = $chapter->unique_phrase_ids === null || $phraseIds !== $currentPhraseIds;

        return [
            "changed" => $wordChanged || $phraseChanged,
            "word_ids" => $wordChanged ? $wordIds : null,
            "phrase_ids" => $phraseChanged ? $phraseIds : null,
            "word_ids_added" => $wordChanged ? count(array_diff($wordIds, $currentWordIds)) : 0,
            "word_ids_removed" => $wordChanged ? count(array_diff($currentWordIds, $wordIds)) : 0,
            "phrase_ids_added" => $phraseChanged ? count(array_diff($phraseIds, $currentPhraseIds)) : 0,
            "phrase_ids_removed" => $phraseChanged ? count(array_diff($currentPhraseIds, $phraseIds)) : 0,
            "ambiguities" => $ambiguities,
            "unresolved_words" => $unresolvedWords,
        ];
    }
}
