<?php

namespace Tests\Feature;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CleanupNonWordVocabularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_invalid_tokens_without_changing_database(): void
    {
        $user = User::factory()->create();
        $invalid = $this->word($user, "+10d6", 2);
        $valid = $this->word($user, "hello", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", ["--user-id" => $user->id]);
        $output = Artisan::output();

        $invalid->refresh();
        $valid->refresh();

        $this->assertStringContainsString('"mode": "dry-run"', $output);
        $this->assertStringContainsString('"invalid": 1', $output);
        $this->assertStringContainsString('"would_ignore": 1', $output);
        $this->assertSame(2, $invalid->stage);
        $this->assertSame(2, $valid->stage);
    }

    public function test_apply_marks_invalid_stage_two_tokens_ignored_without_deleting(): void
    {
        $user = User::factory()->create();
        $invalid = $this->word($user, "+2/+4", 2);
        $valid = $this->word($user, "valid", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);

        $invalid->refresh();
        $valid->refresh();

        $this->assertSame(1, $invalid->stage);
        $this->assertSame(2, $valid->stage);
        $this->assertDatabaseHas("encountered_words", ["id" => $invalid->id, "word" => "+2/+4"]);
    }

    public function test_user_id_filter_only_affects_that_user(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $targetInvalid = $this->word($targetUser, "+1d20", 2);
        $otherInvalid = $this->word($otherUser, "+1d20", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $targetUser->id,
            "--apply" => true,
        ]);

        $targetInvalid->refresh();
        $otherInvalid->refresh();

        $this->assertSame(1, $targetInvalid->stage);
        $this->assertSame(2, $otherInvalid->stage);
    }

    public function test_known_and_learning_invalid_tokens_are_marked_ignored_and_removed_from_review(): void
    {
        $user = User::factory()->create();
        $knownInvalid = $this->word($user, "#", 0);
        $learningInvalid = $this->word($user, "+5d6", -1);
        $newInvalid = $this->word($user, "):", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $knownInvalid->refresh();
        $learningInvalid->refresh();
        $newInvalid->refresh();

        $this->assertStringContainsString('"known_to_ignore": 1', $output);
        $this->assertStringContainsString('"learning_to_ignore": 1', $output);
        $this->assertSame(1, $knownInvalid->stage);
        $this->assertSame(1, $learningInvalid->stage);
        $this->assertSame(1, $newInvalid->stage);
        $this->assertNull($learningInvalid->next_review);
    }

    public function test_apply_repairs_chapter_unique_tokens_and_recalculates_book_counts(): void
    {
        $user = User::factory()->create();
        $valid = $this->word($user, "hello", 2);
        $invalid = $this->word($user, "+1d", 2);
        $book = Book::create([
            "user_id" => $user->id,
            "name" => "Dirty Book",
            "language" => "english",
            "word_count" => 2,
        ]);

        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->name = "Dirty Chapter";
        $chapter->read_count = 0;
        $chapter->word_count = 2;
        $chapter->language = "english";
        $chapter->raw_text = "hello +1d";
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->unique_words = json_encode(["hello", "+1d"]);
        $chapter->unique_word_ids = json_encode([$valid->id, $invalid->id]);
        $chapter->unique_phrase_ids = json_encode([]);
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->setProcessedText([(object) ["word" => "hello"], (object) ["word" => "+1d"]]);
        $chapter->save();

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);

        $chapter->refresh();
        $book->refresh();
        $invalid->refresh();

        $this->assertSame(1, $invalid->stage);
        $this->assertSame(["hello"], json_decode($chapter->unique_words));
        $this->assertSame([$valid->id], json_decode($chapter->unique_word_ids));
        $this->assertSame(1, $chapter->word_count);
        $this->assertSame(1, $book->word_count);
    }

    private function word(User $user, string $word, int $stage, string $language = "english"): EncounteredWord
    {
        return EncounteredWord::forceCreate([
            "user_id" => $user->id,
            "language" => $language,
            "stage" => $stage,
            "word" => $word,
            "base_word" => "",
            "base_word_reading" => "",
            "kanji" => "",
            "reading" => "",
            "lemma" => "",
            "translation" => "",
        ]);
    }
}
