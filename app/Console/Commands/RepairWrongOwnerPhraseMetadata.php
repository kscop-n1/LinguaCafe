<?php

namespace App\Console\Commands;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Chapter;
use App\Models\Phrase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairWrongOwnerPhraseMetadata extends Command
{
    protected $signature = "linguacafe:repair-wrong-owner-phrase-metadata
        {--apply : Apply only unambiguous user/language-scoped phrase remaps. Without this option the command is a dry-run.}
        {--user-id= : Restrict repair to a single chapter owner id.}
        {--language= : Restrict repair to one chapter language.}
        {--book-id= : Restrict repair to one book id.}
        {--chapter-id= : Restrict repair to one chapter id.}
        {--chunk=100 : Number of chapters to scan per chunk.}";

    protected $description = "Report and safely remap wrong-owner phrase IDs in chapter metadata.";

    private array $phraseIndexes = [];

    public function handle(): int
    {
        $this->phraseIndexes = [];
        $apply = (bool) $this->option("apply");
        $chunkSize = max(1, intval($this->option("chunk") ?: 100));
        $summary = [
            "mode" => $apply ? "apply" : "dry-run",
            "chapters_scanned" => 0,
            "chapters_with_wrong_owner_ids" => 0,
            "chapters_would_change" => 0,
            "chapters_changed" => 0,
            "wrong_owner_phrase_ids" => 0,
            "planned_remaps" => 0,
            "unique_metadata_ids_would_remap" => 0,
            "unique_metadata_ids_would_add" => 0,
            "processed_text_ids_would_remap" => 0,
            "unresolved" => 0,
            "ambiguous" => 0,
            "candidates" => [],
        ];

        $query = Chapter::query()
            ->where("processing_status", ChapterProcessingStatusEnum::PROCESSED->value)
            ->select([
                "id",
                "user_id",
                "book_id",
                "language",
                "unique_phrase_ids",
                "processed_text",
            ])
            ->orderBy("id");

        $this->applyScope($query);

        $query->chunkById($chunkSize, function ($chapters) use ($apply, &$summary) {
            foreach ($chapters as $chapter) {
                $summary["chapters_scanned"]++;
                $repair = $this->buildRepair($chapter);

                if (!count($repair["candidates"])) {
                    continue;
                }

                $summary["chapters_with_wrong_owner_ids"]++;
                $summary["wrong_owner_phrase_ids"] += count($repair["candidates"]);
                $summary["planned_remaps"] += count(array_filter(
                    $repair["candidates"],
                    fn ($candidate) => $candidate["status"] === "remap"
                ));
                $summary["unresolved"] += count(array_filter(
                    $repair["candidates"],
                    fn ($candidate) => $candidate["status"] === "unresolved"
                ));
                $summary["ambiguous"] += count(array_filter(
                    $repair["candidates"],
                    fn ($candidate) => $candidate["status"] === "ambiguous"
                ));
                $summary["unique_metadata_ids_would_remap"] += $repair["unique_metadata_ids_remapped"];
                $summary["unique_metadata_ids_would_add"] += $repair["unique_metadata_ids_added"];
                $summary["processed_text_ids_would_remap"] += $repair["processed_text_ids_remapped"];
                array_push($summary["candidates"], ...$repair["candidates"]);

                if (!$repair["changed"]) {
                    continue;
                }

                $summary["chapters_would_change"]++;

                if (!$apply) {
                    continue;
                }

                DB::transaction(function () use ($chapter, &$summary) {
                    $lockedChapter = Chapter::query()
                        ->where("id", $chapter->id)
                        ->where("user_id", $chapter->user_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedChapter) {
                        return;
                    }

                    $lockedRepair = $this->buildRepair($lockedChapter);
                    if (!$lockedRepair["changed"]) {
                        return;
                    }

                    $lockedChapter->unique_phrase_ids = json_encode($lockedRepair["phrase_ids"]);
                    if ($lockedRepair["processed_text_changed"]) {
                        $lockedChapter->setProcessedText($lockedRepair["processed_text"]);
                    }
                    $lockedChapter->save();
                    $summary["chapters_changed"]++;
                });
            }
        }, "id");

        Log::info(
            "Wrong-owner phrase metadata repair completed.",
            array_diff_key($summary, ["candidates" => true])
        );
        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            $this->warn("Dry-run only. Review every remap, unresolved ID, and ambiguity before using --apply.");
        }

        return self::SUCCESS;
    }

    private function applyScope($query): void
    {
        foreach ([
            "user-id" => "user_id",
            "book-id" => "book_id",
            "chapter-id" => "id",
        ] as $option => $column) {
            $value = $this->option($option);
            if ($value !== null && $value !== "") {
                $query->where($column, intval($value));
            }
        }

        $language = $this->option("language");
        if ($language !== null && $language !== "") {
            $query->where("language", $language);
        }
    }

    private function buildRepair(Chapter $chapter): array
    {
        $currentPhraseIds = array_values(array_unique(array_map(
            "intval",
            json_decode($chapter->unique_phrase_ids) ?: []
        )));
        $processedText = $chapter->getProcessedText() ?: [];
        $metadataPhraseIds = $currentPhraseIds;

        foreach ($processedText as $processedWord) {
            foreach (($processedWord->phrase_ids ?? []) as $phraseId) {
                $metadataPhraseIds[] = intval($phraseId);
            }
        }

        $metadataPhraseIds = array_values(array_unique($metadataPhraseIds));
        if (!count($metadataPhraseIds)) {
            return $this->emptyRepair($currentPhraseIds, $processedText);
        }

        $sourcePhrases = Phrase::query()
            ->whereIn("id", $metadataPhraseIds)
            ->get(["id", "user_id", "language", "words"])
            ->keyBy("id");
        $replacementMap = [];
        $candidates = [];

        foreach ($metadataPhraseIds as $phraseId) {
            $sourcePhrase = $sourcePhrases->get($phraseId);
            if ($sourcePhrase
                && intval($sourcePhrase->user_id) === intval($chapter->user_id)
                && $sourcePhrase->language === $chapter->language) {
                continue;
            }

            $candidate = [
                "chapter_id" => intval($chapter->id),
                "user_id" => intval($chapter->user_id),
                "book_id" => intval($chapter->book_id),
                "language" => $chapter->language,
                "source_phrase_id" => intval($phraseId),
                "source_user_id" => $sourcePhrase ? intval($sourcePhrase->user_id) : null,
                "source_language" => $sourcePhrase?->language,
                "normalized_phrase" => null,
                "replacement_phrase_id" => null,
                "replacement_candidate_ids" => [],
                "status" => "unresolved",
                "reason" => $sourcePhrase ? "no_scoped_replacement" : "missing_source_phrase",
            ];

            if ($sourcePhrase) {
                $normalizedPhrase = $this->normalizePhrase($sourcePhrase->words);
                $candidate["normalized_phrase"] = $normalizedPhrase;

                if ($normalizedPhrase !== null) {
                    $replacementCandidates = $this->phraseIndex(
                        intval($chapter->user_id),
                        $chapter->language
                    )[$normalizedPhrase] ?? [];
                    $candidate["replacement_candidate_ids"] = $replacementCandidates;

                    if (count($replacementCandidates) === 1) {
                        $replacementId = intval($replacementCandidates[0]);
                        $replacementMap[intval($phraseId)] = $replacementId;
                        $candidate["replacement_phrase_id"] = $replacementId;
                        $candidate["status"] = "remap";
                        $candidate["reason"] = "unique_scoped_phrase_match";
                    } elseif (count($replacementCandidates) > 1) {
                        $candidate["status"] = "ambiguous";
                        $candidate["reason"] = "multiple_scoped_phrase_matches";
                    }
                } else {
                    $candidate["reason"] = "invalid_source_phrase_words";
                }
            }

            $candidates[] = $candidate;
        }

        $phraseIds = array_values(array_unique(array_map(
            fn ($phraseId) => $replacementMap[intval($phraseId)] ?? intval($phraseId),
            $currentPhraseIds
        )));
        $uniqueMetadataIdsRemapped = count(array_filter(
            $currentPhraseIds,
            fn ($phraseId) => isset($replacementMap[intval($phraseId)])
        ));
        $uniqueMetadataIdsAdded = 0;
        $processedTextIdsRemapped = 0;

        foreach ($processedText as $processedWord) {
            if (!isset($processedWord->phrase_ids) || !is_array($processedWord->phrase_ids)) {
                continue;
            }

            $processedWord->phrase_ids = array_values(array_unique(array_map(
                function ($phraseId) use (
                    $replacementMap,
                    &$phraseIds,
                    &$uniqueMetadataIdsAdded,
                    &$processedTextIdsRemapped
                ) {
                    $phraseId = intval($phraseId);
                    if (isset($replacementMap[$phraseId])) {
                        $processedTextIdsRemapped++;
                        if (!in_array($replacementMap[$phraseId], $phraseIds, true)) {
                            $phraseIds[] = $replacementMap[$phraseId];
                            $uniqueMetadataIdsAdded++;
                        }
                        return $replacementMap[$phraseId];
                    }

                    return $phraseId;
                },
                $processedWord->phrase_ids
            )));
        }

        $phraseIds = array_values(array_unique($phraseIds));
        sort($phraseIds);
        $sortedCurrentPhraseIds = $currentPhraseIds;
        sort($sortedCurrentPhraseIds);

        return [
            "changed" => $phraseIds !== $sortedCurrentPhraseIds || $processedTextIdsRemapped > 0,
            "phrase_ids" => $phraseIds,
            "processed_text" => $processedText,
            "processed_text_changed" => $processedTextIdsRemapped > 0,
            "unique_metadata_ids_remapped" => $uniqueMetadataIdsRemapped,
            "unique_metadata_ids_added" => $uniqueMetadataIdsAdded,
            "processed_text_ids_remapped" => $processedTextIdsRemapped,
            "candidates" => $candidates,
        ];
    }

    private function emptyRepair(array $phraseIds, array $processedText): array
    {
        return [
            "changed" => false,
            "phrase_ids" => $phraseIds,
            "processed_text" => $processedText,
            "processed_text_changed" => false,
            "unique_metadata_ids_remapped" => 0,
            "unique_metadata_ids_added" => 0,
            "processed_text_ids_remapped" => 0,
            "candidates" => [],
        ];
    }

    private function phraseIndex(int $userId, string $language): array
    {
        $scopeKey = $userId . ":" . $language;
        if (isset($this->phraseIndexes[$scopeKey])) {
            return $this->phraseIndexes[$scopeKey];
        }

        $index = [];
        foreach (Phrase::query()
            ->where("user_id", $userId)
            ->where("language", $language)
            ->get(["id", "words"]) as $phrase) {
            $normalizedPhrase = $this->normalizePhrase($phrase->words);
            if ($normalizedPhrase === null) {
                continue;
            }

            $index[$normalizedPhrase][] = intval($phrase->id);
        }

        return $this->phraseIndexes[$scopeKey] = $index;
    }

    private function normalizePhrase(?string $words): ?string
    {
        $decodedWords = json_decode((string) $words, true);
        if (!is_array($decodedWords) || !count($decodedWords)) {
            return null;
        }

        $normalizedWords = [];
        foreach ($decodedWords as $word) {
            if (!is_string($word)) {
                return null;
            }

            $normalizedWord = trim(mb_strtolower($word, "UTF-8"));
            if (class_exists(\Normalizer::class)) {
                $unicodeNormalized = \Normalizer::normalize($normalizedWord, \Normalizer::FORM_C);
                if ($unicodeNormalized === false) {
                    return null;
                }
                $normalizedWord = $unicodeNormalized;
            }

            if ($normalizedWord === "") {
                return null;
            }

            $normalizedWords[] = $normalizedWord;
        }

        return json_encode($normalizedWords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
