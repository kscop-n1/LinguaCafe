<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Services\TextBlockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupNonWordVocabulary extends Command
{
    protected $signature = "linguacafe:cleanup-non-word-vocabulary
        {--apply : Apply cleanup. Without this option the command only reports what would change.}
        {--user-id= : Restrict cleanup to a single user id.}
        {--language= : Restrict cleanup to one language.}
        {--chunk=500 : Number of rows to scan per chunk.}";

    protected $description = "Dry-run or mark existing non-word vocabulary tokens as ignored and repair chapter metadata.";

    public function handle(): int
    {
        $apply = (bool) $this->option("apply");
        $userId = $this->option("user-id");
        $language = $this->option("language");
        $chunkSize = max(1, intval($this->option("chunk") ?: 500));

        $query = EncounteredWord::query()
            ->select(["id", "user_id", "language", "word", "stage"])
            ->orderBy("id");

        if ($userId !== null && $userId !== "") {
            $query->where("user_id", intval($userId));
        }

        if ($language !== null && $language !== "") {
            $query->where("language", $language);
        }

        $summary = [
            "mode" => $apply ? "apply" : "dry-run",
            "scanned" => 0,
            "invalid" => 0,
            "would_ignore" => 0,
            "ignored" => 0,
            "already_ignored" => 0,
            "known_to_ignore" => 0,
            "learning_to_ignore" => 0,
            "chapters_repaired" => 0,
            "books_recalculated" => 0,
            "samples" => [],
        ];

        $invalidIdsByScope = [];
        $invalidWordsByScope = [];
        $affectedBookIdsByScope = [];

        $query->chunkById($chunkSize, function ($words) use ($apply, &$summary, &$invalidIdsByScope, &$invalidWordsByScope) {
            $idsToIgnore = [];

            foreach ($words as $word) {
                $summary["scanned"]++;

                if (TextBlockService::isVocabularyToken($word->word, $word->language)) {
                    continue;
                }

                $summary["invalid"]++;
                $scopeKey = $word->user_id . ":" . $word->language;
                $invalidIdsByScope[$scopeKey][intval($word->id)] = true;
                $invalidWordsByScope[$scopeKey][mb_strtolower($word->word, "UTF-8")] = true;

                if (count($summary["samples"]) < 20) {
                    $summary["samples"][] = [
                        "id" => $word->id,
                        "user_id" => $word->user_id,
                        "language" => $word->language,
                        "word" => $word->word,
                        "stage" => $word->stage,
                    ];
                }

                if ($word->stage === 1) {
                    $summary["already_ignored"]++;
                    continue;
                }

                if ($word->stage === 0) {
                    $summary["known_to_ignore"]++;
                }

                if ($word->stage < 0) {
                    $summary["learning_to_ignore"]++;
                }

                $summary["would_ignore"]++;
                $idsToIgnore[] = $word->id;
            }

            if ($apply && count($idsToIgnore)) {
                EncounteredWord::query()
                    ->whereIn("id", $idsToIgnore)
                    ->update([
                        "stage" => 1,
                        "next_review" => null,
                        "relearning" => false,
                    ]);

                $summary["ignored"] += count($idsToIgnore);
                Log::info("Marked non-word vocabulary tokens as ignored.", ["ids" => $idsToIgnore]);
            }
        }, "id");

        if ($apply && count($invalidIdsByScope)) {
            DB::transaction(function () use ($invalidIdsByScope, $invalidWordsByScope, &$affectedBookIdsByScope, &$summary) {
                foreach ($invalidIdsByScope as $scopeKey => $invalidIdMap) {
                    [$scopeUserId, $scopeLanguage] = explode(":", $scopeKey, 2);
                    $invalidIds = array_map("intval", array_keys($invalidIdMap));
                    $invalidWords = array_keys($invalidWordsByScope[$scopeKey] ?? []);

                    Chapter::query()
                        ->where("user_id", intval($scopeUserId))
                        ->where("language", $scopeLanguage)
                        ->select(["id", "book_id", "unique_words", "unique_word_ids", "word_count", "processed_text"])
                        ->orderBy("id")
                        ->chunkById(100, function ($chapters) use ($scopeKey, $invalidIds, $invalidWords, &$affectedBookIdsByScope, &$summary) {
                            foreach ($chapters as $chapter) {
                                $changed = false;
                                $uniqueWordIds = json_decode($chapter->unique_word_ids) ?: [];
                                $uniqueWords = json_decode($chapter->unique_words) ?: [];

                                $filteredWordIds = array_values(array_filter($uniqueWordIds, function ($wordId) use ($invalidIds) {
                                    return !in_array(intval($wordId), $invalidIds, true);
                                }));

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
                                    $wordCount = 0;
                                    foreach ($processedText as $processedWord) {
                                        if (TextBlockService::isVocabularyToken($processedWord->word ?? "", $chapter->language)) {
                                            $wordCount++;
                                        }
                                    }

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
                    [$scopeUserId] = explode(":", $scopeKey, 2);
                    foreach (array_keys($bookIdMap) as $bookId) {
                        $wordCount = Chapter::query()
                            ->where("user_id", intval($scopeUserId))
                            ->where("book_id", intval($bookId))
                            ->sum("word_count");

                        Book::query()
                            ->where("user_id", intval($scopeUserId))
                            ->where("id", intval($bookId))
                            ->update(["word_count" => intval($wordCount)]);

                        $summary["books_recalculated"]++;
                    }
                }
            });
        }

        Log::info("Non-word vocabulary cleanup completed.", $summary);
        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            $this->warn("Dry-run only. Re-run with --apply to mark invalid tokens ignored and repair chapter metadata.");
        }

        return self::SUCCESS;
    }
}
