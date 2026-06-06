<?php

namespace App\Console\Commands;

use App\Models\EncounteredWord;
use App\Services\TextBlockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupNonWordVocabulary extends Command
{
    protected $signature = "linguacafe:cleanup-non-word-vocabulary
        {--apply : Apply cleanup. Without this option the command only reports what would change.}
        {--user-id= : Restrict cleanup to a single user id.}
        {--language= : Restrict cleanup to one language.}
        {--chunk=500 : Number of rows to scan per chunk.}";

    protected $description = "Dry-run or mark existing non-word vocabulary tokens as ignored.";

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
            "skipped_known" => 0,
            "skipped_learning" => 0,
            "samples" => [],
        ];

        $query->chunkById($chunkSize, function ($words) use ($apply, &$summary) {
            $idsToIgnore = [];

            foreach ($words as $word) {
                $summary["scanned"]++;

                if (TextBlockService::isVocabularyToken($word->word, $word->language)) {
                    continue;
                }

                $summary["invalid"]++;
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
                    $summary["skipped_known"]++;
                    continue;
                }

                if ($word->stage < 0) {
                    $summary["skipped_learning"]++;
                    continue;
                }

                if ($word->stage === 2) {
                    $summary["would_ignore"]++;
                    $idsToIgnore[] = $word->id;
                }
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

        Log::info("Non-word vocabulary cleanup completed.", $summary);
        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            $this->warn("Dry-run only. Re-run with --apply to mark safe invalid stage-2 tokens as ignored.");
        }

        return self::SUCCESS;
    }
}
