<?php

namespace Tests\Feature;

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

    public function test_known_and_learning_invalid_tokens_are_reported_but_not_changed(): void
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

        $this->assertStringContainsString('"skipped_known": 1', $output);
        $this->assertStringContainsString('"skipped_learning": 1', $output);
        $this->assertSame(0, $knownInvalid->stage);
        $this->assertSame(-1, $learningInvalid->stage);
        $this->assertSame(1, $newInvalid->stage);
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
