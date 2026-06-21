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
            ...$this->safeDeleteOptions($user, "1d6+db"),
        ]);

        $valid->refresh();

        $this->assertSame(2, $valid->stage);
        $this->assertDatabaseMissing("encountered_words", ["id" => $invalid->id]);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            ...$this->safeDeleteOptions($user, "1d6+db"),
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
            ...$this->safeDeleteOptions($targetUser, "+1d20"),
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
            ...$this->quarantineOptions($user, ["#", "+5d6"]),
        ]);
        $quarantineOutput = Artisan::output();
        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            ...$this->safeDeleteOptions($user, "):"),
        ]);
        $deleteOutput = Artisan::output();

        $knownInvalid->refresh();
        $learningInvalid->refresh();

        $this->assertStringContainsString('"quarantined": 2', $quarantineOutput);
        $this->assertStringContainsString('"deleted": 1', $deleteOutput);
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
            ...$this->safeDeleteOptions($user, "abc_def"),
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
            ...$this->safeDeleteOptions($user, "+1d"),
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

    public function test_dry_run_filters_by_reason(): void
    {
        $user = User::factory()->create();
        $dice = $this->word($user, "+10d6", 2);
        $math = $this->word($user, "+2/+4", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--reason" => ["dice_notation"],
            "--report-only-json" => true,
        ]);
        $summary = $this->summary();

        $this->assertSame(2, $summary["total_candidate_count"]);
        $this->assertSame(1, $summary["selected_candidate_count"]);
        $this->assertSame(1, $summary["excluded_candidate_count"]);
        $this->assertSame("+10d6", $summary["candidates"][0]["token"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $dice->id]);
        $this->assertDatabaseHas("encountered_words", ["id" => $math->id]);
    }

    public function test_dry_run_filters_by_exact_token(): void
    {
        $user = User::factory()->create();
        $selected = $this->word($user, "+10d6", 2);
        $excluded = $this->word($user, "+2d20", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => ["+10d6"],
            "--report-only-json" => true,
        ]);
        $summary = $this->summary();

        $this->assertSame(2, $summary["total_candidate_count"]);
        $this->assertSame(1, $summary["selected_candidate_count"]);
        $this->assertSame(1, $summary["excluded_candidate_count"]);
        $this->assertSame("+10d6", $summary["candidates"][0]["token"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $selected->id]);
        $this->assertDatabaseHas("encountered_words", ["id" => $excluded->id]);
    }

    public function test_dry_run_excludes_token_and_marks_allowed_token_as_no_action(): void
    {
        $user = User::factory()->create();
        $excluded = $this->word($user, "+1d20", 2);
        $allowed = $this->word($user, "+2d20", 2);
        $selected = $this->word($user, "+3d20", 2);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--reason" => ["dice_notation"],
            "--exclude-token" => ["+1d20"],
            "--allow-token" => ["+2d20"],
            "--report-only-json" => true,
        ]);
        $summary = $this->summary();
        $candidates = collect($summary["candidates"])->keyBy("token");

        $this->assertSame(2, $summary["selected_candidate_count"]);
        $this->assertSame(1, $summary["excluded_candidate_count"]);
        $this->assertTrue($candidates["+2d20"]["allowed_no_action"]);
        $this->assertSame("no_action", $candidates["+2d20"]["candidate_action"]);
        $this->assertSame("safe_delete", $candidates["+3d20"]["candidate_action"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $excluded->id]);
        $this->assertDatabaseHas("encountered_words", ["id" => $allowed->id]);
        $this->assertDatabaseHas("encountered_words", ["id" => $selected->id]);
    }

    public function test_dry_run_filters_candidates_by_book_and_chapter_scope(): void
    {
        $user = User::factory()->create();
        $bookA = $this->book($user, "Book A");
        $bookB = $this->book($user, "Book B");
        $wordA = $this->word($user, "+1d20", 2);
        $wordB = $this->word($user, "+2d20", 2);
        $chapterA = $this->chapter($user, $bookA, $wordA);
        $this->chapter($user, $bookB, $wordB);

        Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--book-id" => $bookA->id,
            "--chapter-id" => $chapterA->id,
            "--report-only-json" => true,
        ]);
        $summary = $this->summary();

        $this->assertSame(1, $summary["selected_candidate_count"]);
        $this->assertSame("+1d20", $summary["candidates"][0]["token"]);
        $this->assertSame($bookA->id, $summary["selected_scope"]["book_id"]);
        $this->assertSame($chapterA->id, $summary["selected_scope"]["chapter_id"]);
    }

    public function test_apply_fails_without_user_id(): void
    {
        $user = User::factory()->create();
        $word = $this->word($user, "+1d20", 2);
        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--language" => "english",
            "--token" => ["+1d20"],
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains("user_id_required", $this->summary()["apply_ineligible_reasons"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $word->id]);
    }

    public function test_apply_fails_without_language(): void
    {
        $user = User::factory()->create();
        $word = $this->word($user, "+1d20", 2);
        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--token" => ["+1d20"],
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains("language_required", $this->summary()["apply_ineligible_reasons"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $word->id]);
    }

    public function test_apply_fails_without_positive_selector(): void
    {
        $user = User::factory()->create();
        $word = $this->word($user, "+1d20", 2);
        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains("positive_selector_required", $this->summary()["apply_ineligible_reasons"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $word->id]);
    }

    public function test_apply_fails_without_max_candidates(): void
    {
        $user = User::factory()->create();
        $word = $this->word($user, "+1d20", 2);
        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => ["+1d20"],
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertContains("max_candidates_required", $this->summary()["apply_ineligible_reasons"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $word->id]);
    }

    public function test_apply_fails_when_selected_count_exceeds_max_candidates(): void
    {
        $user = User::factory()->create();
        $first = $this->word($user, "+1d20", 2);
        $second = $this->word($user, "+1d20", 2);
        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => ["+1d20"],
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $summary = $this->summary();
        $this->assertSame(1, $exitCode);
        $this->assertSame(2, $summary["selected_actionable_count"]);
        $this->assertContains(
            "selected_actionable_count_exceeds_max_candidates",
            $summary["apply_ineligible_reasons"]
        );
        $this->assertDatabaseHas("encountered_words", ["id" => $first->id]);
        $this->assertDatabaseHas("encountered_words", ["id" => $second->id]);
    }

    public function test_apply_fails_with_both_or_plain_apply_modes(): void
    {
        $user = User::factory()->create();
        $word = $this->word($user, "+1d20", 2);
        $baseOptions = [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => ["+1d20"],
            "--max-candidates" => 1,
            "--report-only-json" => true,
        ];

        $bothExitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            ...$baseOptions,
            "--apply-safe-delete-only" => true,
            "--apply-quarantine-only" => true,
        ]);
        $this->assertSame(1, $bothExitCode);
        $this->assertContains(
            "exactly_one_apply_action_mode_required",
            $this->summary()["apply_ineligible_reasons"]
        );

        $plainExitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            ...$baseOptions,
            "--apply" => true,
        ]);
        $this->assertSame(1, $plainExitCode);
        $this->assertContains(
            "exactly_one_apply_action_mode_required",
            $this->summary()["apply_ineligible_reasons"]
        );
        $this->assertDatabaseHas("encountered_words", ["id" => $word->id]);
    }

    public function test_apply_quarantine_only_does_not_delete_safe_candidates(): void
    {
        $user = User::factory()->create();
        $history = $this->word($user, "+1d20", -1);
        $history->translation = "reviewed";
        $history->save();
        $pristine = $this->word($user, "+2d20", 2);

        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            ...$this->quarantineOptions($user, ["+1d20", "+2d20"]),
            "--report-only-json" => true,
        ]);

        $history->refresh();
        $summary = $this->summary();
        $this->assertSame(0, $exitCode);
        $this->assertSame(1, $history->stage);
        $this->assertDatabaseHas("encountered_words", ["id" => $pristine->id, "stage" => 2]);
        $this->assertSame(1, $summary["quarantined"]);
        $this->assertSame(0, $summary["deleted"]);
    }

    public function test_apply_safe_delete_only_does_not_quarantine_history_candidates(): void
    {
        $user = User::factory()->create();
        $pristine = $this->word($user, "+1d20", 2);
        $history = $this->word($user, "+2d20", -1);
        $history->translation = "reviewed";
        $history->save();

        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => ["+1d20", "+2d20"],
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $history->refresh();
        $summary = $this->summary();
        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing("encountered_words", ["id" => $pristine->id]);
        $this->assertSame(-1, $history->stage);
        $this->assertSame("reviewed", $history->translation);
        $this->assertSame(1, $summary["deleted"]);
        $this->assertSame(0, $summary["quarantined"]);
    }

    public function test_reviewed_allow_token_does_not_block_a_safe_scoped_apply(): void
    {
        $user = User::factory()->create();
        $allowed = $this->word($user, "abc_def", 2);
        $safe = $this->word($user, "+1d20", 2);

        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => ["abc_def", "+1d20"],
            "--allow-token" => ["abc_def"],
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);

        $summary = $this->summary();
        $this->assertSame(0, $exitCode);
        $this->assertTrue($summary["apply_eligible"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $allowed->id, "stage" => 2]);
        $this->assertDatabaseMissing("encountered_words", ["id" => $safe->id]);
        $this->assertSame(1, $summary["deleted"]);
        $this->assertSame(1, $summary["no_action_count"]);
    }

    public function test_manual_review_reason_is_never_mutated_automatically(): void
    {
        $user = User::factory()->create();
        $ambiguous = $this->word($user, "abc_def", 2);

        $exitCode = Artisan::call("linguacafe:cleanup-non-word-vocabulary", [
            "--user-id" => $user->id,
            "--language" => "english",
            "--reason" => ["unknown_suspicious_token"],
            "--max-candidates" => 1,
            "--apply-safe-delete-only" => true,
            "--report-only-json" => true,
        ]);
        $summary = $this->summary();

        $this->assertSame(1, $exitCode);
        $this->assertContains(
            "unsafe_reason_not_actionable:unknown_suspicious_token",
            $summary["apply_ineligible_reasons"]
        );
        $this->assertSame(0, $summary["selected_actionable_count"]);
        $this->assertDatabaseHas("encountered_words", ["id" => $ambiguous->id, "stage" => 2]);
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

    private function safeDeleteOptions(User $user, string $token): array
    {
        return [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => [$token],
            "--max-candidates" => 10,
            "--apply-safe-delete-only" => true,
        ];
    }

    private function quarantineOptions(User $user, array $tokens): array
    {
        return [
            "--user-id" => $user->id,
            "--language" => "english",
            "--token" => $tokens,
            "--max-candidates" => 10,
            "--apply-quarantine-only" => true,
        ];
    }

    private function summary(): array
    {
        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function book(User $user, string $name): Book
    {
        return Book::create([
            "user_id" => $user->id,
            "name" => $name,
            "language" => "english",
            "word_count" => 1,
        ]);
    }

    private function chapter(User $user, Book $book, EncounteredWord $word): Chapter
    {
        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->name = "Scoped Chapter";
        $chapter->read_count = 0;
        $chapter->word_count = 1;
        $chapter->language = "english";
        $chapter->raw_text = $word->word;
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->unique_words = json_encode([$word->word]);
        $chapter->unique_word_ids = json_encode([$word->id]);
        $chapter->unique_phrase_ids = json_encode([]);
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->setProcessedText([(object) ["word" => $word->word, "phrase_ids" => []]]);
        $chapter->save();

        return $chapter;
    }
}
