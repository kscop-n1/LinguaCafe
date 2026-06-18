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
