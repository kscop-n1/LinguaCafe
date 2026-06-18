<?php

namespace Tests\Feature;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
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
        $this->assertStringContainsString('"dice_notation": 1', $output);
        $this->assertStringContainsString('"reason": "dice_notation"', $output);
        $this->assertStringContainsString('"safe_to_delete": true', $output);
        $this->assertStringContainsString('"manual_review": false', $output);
        $this->assertSame(2, $invalid->stage);
        $this->assertSame(2, $valid->stage);
    }

    public function test_apply_deletes_safe_invalid_rows_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $invalid = $this->word($user, "1d6+db", 2);
        $valid = $this->word($user, "valid", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);

        $valid->refresh();

        $this->assertSame(2, $valid->stage);
        $this->assertDatabaseMissing("encountered_words", ["id" => $invalid->id]);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);

        $this->assertStringContainsString('"invalid": 0', Artisan::output());
        $this->assertDatabaseHas("encountered_words", ["id" => $valid->id]);
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

        $otherInvalid->refresh();

        $this->assertDatabaseMissing("encountered_words", ["id" => $targetInvalid->id]);
        $this->assertSame(2, $otherInvalid->stage);
    }

    public function test_known_and_learning_invalid_tokens_are_marked_ignored_and_removed_from_review(): void
    {
        $user = User::factory()->create();
        $knownInvalid = $this->word($user, "#", 0);
        $learningInvalid = $this->word($user, "+5d6", -1);
        $newInvalid = $this->word($user, "):", 2);
        $learningInvalid->translation = "user note";
        $learningInvalid->save();

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $knownInvalid->refresh();
        $learningInvalid->refresh();

        $this->assertStringContainsString('"quarantined": 2', $output);
        $this->assertStringContainsString('"deleted": 1', $output);
        $this->assertSame(1, $knownInvalid->stage);
        $this->assertSame(1, $learningInvalid->stage);
        $this->assertDatabaseMissing("encountered_words", ["id" => $newInvalid->id]);
        $this->assertNull($learningInvalid->next_review);
    }

    public function test_apply_does_not_mutate_unknown_suspicious_tokens(): void
    {
        $user = User::factory()->create();
        $ambiguous = $this->word($user, "abc_def", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--apply" => true,
        ]);
        $output = Artisan::output();

        $ambiguous->refresh();
        $this->assertSame(2, $ambiguous->stage);
        $this->assertStringContainsString('"manual_review": true', $output);
        $this->assertStringContainsString('"ambiguous": 1', $output);
        $this->assertStringContainsString('"skipped_ambiguous": 1', $output);
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

        $this->assertDatabaseMissing("encountered_words", ["id" => $invalid->id]);
        $this->assertSame(["hello"], json_decode($chapter->unique_words));
        $this->assertSame([$valid->id], json_decode($chapter->unique_word_ids));
        $this->assertSame(1, $chapter->word_count);
        $this->assertSame(1, $book->word_count);
    }

    public function test_dry_run_reports_chapter_book_and_review_associations(): void
    {
        $user = User::factory()->create();
        $invalid = $this->word($user, "+1d20", -2);
        $invalid->next_review = Carbon::today()->toDateString();
        $invalid->translation = "custom";
        $invalid->save();
        $book = Book::create([
            "user_id" => $user->id,
            "name" => "Associated Book",
            "language" => "english",
            "word_count" => 1,
        ]);
        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->name = "Associated Chapter";
        $chapter->read_count = 0;
        $chapter->word_count = 1;
        $chapter->language = "english";
        $chapter->raw_text = "+1d20";
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->unique_words = json_encode(["+1d20"]);
        $chapter->unique_word_ids = json_encode([$invalid->id]);
        $chapter->unique_phrase_ids = json_encode([]);
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->setProcessedText([(object) ["word" => "+1d20", "phrase_ids" => []]]);
        $chapter->save();

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", ["--user-id" => $user->id]);
        $output = Artisan::output();

        $this->assertStringContainsString('"review_records": 1', $output);
        $this->assertStringContainsString('"highlighted_records": 1', $output);
        $this->assertStringContainsString('"chapter_ids":', $output);
        $this->assertStringContainsString((string) $chapter->id, $output);
        $this->assertStringContainsString('"book_ids":', $output);
        $this->assertStringContainsString((string) $book->id, $output);
        $this->assertStringContainsString('"safe_to_delete": false', $output);
        $this->assertStringContainsString('"flashcard_records": 0', $output);
    }

    public function test_review_goal_quantity_excludes_invalid_legacy_rows(): void
    {
        $user = User::factory()->create(["selected_language" => "english"]);
        $valid = $this->word($user, "hello", -2);
        $valid->next_review = Carbon::today()->toDateString();
        $valid->save();
        $invalid = $this->word($user, "1d6+db", -2);
        $invalid->next_review = Carbon::today()->toDateString();
        $invalid->save();

        $this->actingAs($user);

        $this->assertSame(1, (new Goal())->getTodaysReviewGoalQuantity());
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
