<?php

namespace Tests\Feature;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackfillVocabularyMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_and_apply_backfill_missing_word_and_phrase_ids_idempotently(): void
    {
        $user = User::factory()->create();
        $book = $this->book($user);
        $alpha = $this->word($user, "alpha");
        $beta = $this->word($user, "beta");
        $phrase = Phrase::forceCreate([
            "user_id" => $user->id,
            "language" => "english",
            "words" => json_encode(["alpha", "beta"]),
            "words_searchable" => "alpha beta",
            "reading" => "",
            "translation" => "",
            "stage" => 2,
        ]);
        $legacy = $this->chapter($user, $book, ["alpha", "beta"], null, null, [
            (object) ["word" => "alpha", "phrase_ids" => [$phrase->id]],
            (object) ["word" => "beta", "phrase_ids" => [$phrase->id]],
        ]);
        $current = $this->chapter($user, $book, ["alpha"], [$alpha->id], [], []);

        Artisan::call("linguacafe:backfill-vocabulary-metadata", ["--user-id" => $user->id]);
        $dryRunOutput = Artisan::output();

        $legacy->refresh();
        $current->refresh();
        $this->assertNull($legacy->unique_word_ids);
        $this->assertNull($legacy->unique_phrase_ids);
        $this->assertSame([$alpha->id], json_decode($current->unique_word_ids));
        $this->assertStringContainsString('"chapters_would_change": 1', $dryRunOutput);
        $this->assertStringContainsString('"word_ids_added": 2', $dryRunOutput);
        $this->assertStringContainsString('"phrase_ids_added": 1', $dryRunOutput);

        Artisan::call("linguacafe:backfill-vocabulary-metadata", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);

        $legacy->refresh();
        $current->refresh();
        $this->assertEqualsCanonicalizing([$alpha->id, $beta->id], json_decode($legacy->unique_word_ids));
        $this->assertSame([$phrase->id], json_decode($legacy->unique_phrase_ids));
        $this->assertSame([$alpha->id], json_decode($current->unique_word_ids));

        Artisan::call("linguacafe:backfill-vocabulary-metadata", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);
        $secondOutput = Artisan::output();

        $this->assertStringContainsString('"chapters_changed": 0', $secondOutput);
        $this->assertStringContainsString('"word_ids_added": 0', $secondOutput);
        $this->assertStringContainsString('"phrase_ids_added": 0', $secondOutput);
    }

    public function test_backfill_reports_duplicate_word_text_as_ambiguous_without_mutation(): void
    {
        $user = User::factory()->create();
        $book = $this->book($user);
        $this->word($user, "shared");
        $this->word($user, "shared");
        $chapter = $this->chapter($user, $book, ["shared"], null, [], []);

        Artisan::call("linguacafe:backfill-vocabulary-metadata", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $this->assertNull($chapter->unique_word_ids);
        $this->assertStringContainsString('"ambiguous_chapters": 1', $output);
        $this->assertStringContainsString('"word": "shared"', $output);
        $this->assertStringContainsString('"candidate_count": 2', $output);
    }

    public function test_wrong_owner_phrase_repair_dry_run_reports_unique_scoped_remap_without_mutation(): void
    {
        $sourceUser = User::factory()->create();
        $chapterUser = User::factory()->create();
        $book = $this->book($chapterUser);
        $sourcePhrase = $this->phrase($sourceUser, ["seemed", "more", "apt"]);
        $replacementPhrase = $this->phrase($chapterUser, ["Seemed", "More", "Apt"]);
        $correctPhrase = $this->phrase($chapterUser, ["correct", "phrase"]);
        $chapter = $this->chapter(
            $chapterUser,
            $book,
            [],
            [],
            [$sourcePhrase->id, $correctPhrase->id],
            [
                (object) ["word" => "seemed", "phrase_ids" => [$sourcePhrase->id]],
                (object) ["word" => "correct", "phrase_ids" => [$correctPhrase->id]],
            ]
        );

        Artisan::call("linguacafe:repair-wrong-owner-phrase-metadata", [
            "--user-id" => $chapterUser->id,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $this->assertSame(
            [$sourcePhrase->id, $correctPhrase->id],
            json_decode($chapter->unique_phrase_ids)
        );
        $this->assertSame(
            [$sourcePhrase->id],
            $chapter->getProcessedText()[0]->phrase_ids
        );
        $this->assertStringContainsString('"mode": "dry-run"', $output);
        $this->assertStringContainsString('"planned_remaps": 1', $output);
        $this->assertStringContainsString(
            '"replacement_phrase_id": ' . $replacementPhrase->id,
            $output
        );
        $this->assertStringContainsString('"chapters_changed": 0', $output);
        $this->assertStringNotContainsString(
            '"source_phrase_id": ' . $correctPhrase->id,
            $output
        );
    }

    public function test_wrong_owner_phrase_repair_apply_remaps_unique_and_processed_metadata(): void
    {
        $sourceUser = User::factory()->create();
        $chapterUser = User::factory()->create();
        $book = $this->book($chapterUser);
        $sourcePhrase = $this->phrase($sourceUser, ["go", "stir-crazy"]);
        $replacementPhrase = $this->phrase($chapterUser, ["go", "stir-crazy"]);
        $correctPhrase = $this->phrase($chapterUser, ["stay", "calm"]);
        $chapter = $this->chapter(
            $chapterUser,
            $book,
            [],
            [],
            [$sourcePhrase->id, $correctPhrase->id],
            [
                (object) ["word" => "go", "phrase_ids" => [$sourcePhrase->id, $correctPhrase->id]],
            ]
        );

        Artisan::call("linguacafe:repair-wrong-owner-phrase-metadata", [
            "--user-id" => $chapterUser->id,
            "--language" => "english",
            "--book-id" => $book->id,
            "--chapter-id" => $chapter->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $this->assertEqualsCanonicalizing(
            [$replacementPhrase->id, $correctPhrase->id],
            json_decode($chapter->unique_phrase_ids)
        );
        $this->assertEqualsCanonicalizing(
            [$replacementPhrase->id, $correctPhrase->id],
            $chapter->getProcessedText()[0]->phrase_ids
        );
        $this->assertStringContainsString('"chapters_changed": 1', $output);
        $this->assertStringContainsString('"unique_metadata_ids_would_remap": 1', $output);
        $this->assertStringContainsString('"processed_text_ids_would_remap": 1', $output);
    }

    public function test_wrong_owner_phrase_repair_adds_processed_only_replacement_to_unique_metadata(): void
    {
        $sourceUser = User::factory()->create();
        $chapterUser = User::factory()->create();
        $book = $this->book($chapterUser);
        $sourcePhrase = $this->phrase($sourceUser, ["processed", "only"]);
        $replacementPhrase = $this->phrase($chapterUser, ["processed", "only"]);
        $chapter = $this->chapter(
            $chapterUser,
            $book,
            [],
            [],
            [],
            [(object) ["word" => "processed", "phrase_ids" => [$sourcePhrase->id]]]
        );

        Artisan::call("linguacafe:repair-wrong-owner-phrase-metadata", [
            "--user-id" => $chapterUser->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $this->assertSame([$replacementPhrase->id], json_decode($chapter->unique_phrase_ids));
        $this->assertSame(
            [$replacementPhrase->id],
            $chapter->getProcessedText()[0]->phrase_ids
        );
        $this->assertStringContainsString('"unique_metadata_ids_would_add": 1', $output);
        $this->assertStringContainsString('"processed_text_ids_would_remap": 1', $output);
    }

    public function test_wrong_owner_phrase_repair_reports_missing_replacement_without_mutation(): void
    {
        $sourceUser = User::factory()->create();
        $chapterUser = User::factory()->create();
        $book = $this->book($chapterUser);
        $sourcePhrase = $this->phrase($sourceUser, ["no", "replacement"]);
        $chapter = $this->chapter(
            $chapterUser,
            $book,
            [],
            [],
            [$sourcePhrase->id],
            [(object) ["word" => "no", "phrase_ids" => [$sourcePhrase->id]]]
        );

        Artisan::call("linguacafe:repair-wrong-owner-phrase-metadata", [
            "--user-id" => $chapterUser->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $this->assertSame([$sourcePhrase->id], json_decode($chapter->unique_phrase_ids));
        $this->assertSame([$sourcePhrase->id], $chapter->getProcessedText()[0]->phrase_ids);
        $this->assertStringContainsString('"unresolved": 1', $output);
        $this->assertStringContainsString('"reason": "no_scoped_replacement"', $output);
        $this->assertStringContainsString('"chapters_changed": 0', $output);
    }

    public function test_wrong_owner_phrase_repair_reports_multiple_replacements_as_ambiguous(): void
    {
        $sourceUser = User::factory()->create();
        $chapterUser = User::factory()->create();
        $book = $this->book($chapterUser);
        $sourcePhrase = $this->phrase($sourceUser, ["duplicate", "phrase"]);
        $firstReplacement = $this->phrase($chapterUser, ["duplicate", "phrase"]);
        $secondReplacement = $this->phrase($chapterUser, ["Duplicate", "Phrase"]);
        $chapter = $this->chapter(
            $chapterUser,
            $book,
            [],
            [],
            [$sourcePhrase->id],
            []
        );

        Artisan::call("linguacafe:repair-wrong-owner-phrase-metadata", [
            "--user-id" => $chapterUser->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $this->assertSame([$sourcePhrase->id], json_decode($chapter->unique_phrase_ids));
        $summary = json_decode($output, true);
        $this->assertSame(1, $summary["ambiguous"]);
        $this->assertSame(0, $summary["chapters_changed"]);
        $this->assertSame(
            "multiple_scoped_phrase_matches",
            $summary["candidates"][0]["reason"]
        );
        $this->assertEqualsCanonicalizing(
            [$firstReplacement->id, $secondReplacement->id],
            $summary["candidates"][0]["replacement_candidate_ids"]
        );
    }

    public function test_wrong_owner_phrase_repair_enforces_user_and_language_scope(): void
    {
        $sourceUser = User::factory()->create();
        $chapterUser = User::factory()->create();
        $otherChapterUser = User::factory()->create();
        $book = $this->book($chapterUser);
        $otherBook = $this->book($otherChapterUser);
        $sourcePhrase = $this->phrase($sourceUser, ["scoped", "phrase"]);
        $this->phrase($chapterUser, ["scoped", "phrase"], "spanish");
        $otherReplacement = $this->phrase($otherChapterUser, ["scoped", "phrase"]);
        $chapter = $this->chapter($chapterUser, $book, [], [], [$sourcePhrase->id], []);
        $otherChapter = $this->chapter(
            $otherChapterUser,
            $otherBook,
            [],
            [],
            [$sourcePhrase->id],
            []
        );

        Artisan::call("linguacafe:repair-wrong-owner-phrase-metadata", [
            "--user-id" => $chapterUser->id,
            "--language" => "english",
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $chapter->refresh();
        $otherChapter->refresh();
        $this->assertSame([$sourcePhrase->id], json_decode($chapter->unique_phrase_ids));
        $this->assertSame([$sourcePhrase->id], json_decode($otherChapter->unique_phrase_ids));
        $this->assertStringContainsString('"chapters_scanned": 1', $output);
        $this->assertStringContainsString('"unresolved": 1', $output);
        $this->assertStringNotContainsString(
            '"replacement_phrase_id": ' . $otherReplacement->id,
            $output
        );
    }

    private function book(User $user): Book
    {
        return Book::create([
            "user_id" => $user->id,
            "name" => "Metadata Book",
            "language" => "english",
        ]);
    }

    private function word(User $user, string $word): EncounteredWord
    {
        return EncounteredWord::forceCreate([
            "user_id" => $user->id,
            "language" => "english",
            "stage" => 2,
            "word" => $word,
            "base_word" => "",
            "base_word_reading" => "",
            "kanji" => "",
            "reading" => "",
            "lemma" => "",
            "translation" => "",
        ]);
    }

    private function phrase(User $user, array $words, string $language = "english"): Phrase
    {
        return Phrase::forceCreate([
            "user_id" => $user->id,
            "language" => $language,
            "words" => json_encode($words),
            "words_searchable" => implode(" ", $words),
            "reading" => "",
            "translation" => "",
            "stage" => 2,
        ]);
    }

    private function chapter(
        User $user,
        Book $book,
        array $uniqueWords,
        ?array $uniqueWordIds,
        ?array $uniquePhraseIds,
        array $processedText
    ): Chapter {
        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->name = "Chapter";
        $chapter->read_count = 0;
        $chapter->word_count = count($uniqueWords);
        $chapter->language = "english";
        $chapter->raw_text = implode(" ", $uniqueWords);
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->unique_words = json_encode($uniqueWords);
        $chapter->unique_word_ids = $uniqueWordIds === null ? null : json_encode($uniqueWordIds);
        $chapter->unique_phrase_ids = $uniquePhraseIds === null ? null : json_encode($uniquePhraseIds);
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->setProcessedText($processedText);
        $chapter->save();

        return $chapter;
    }
}
