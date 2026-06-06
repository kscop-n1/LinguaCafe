<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TextBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextBlockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_vocabulary_token_classifier_rejects_reported_non_word_tokens(): void
    {
        foreach (["#", "'s", "):", "+1", "+10", "+10d6", "+1d", "+1d10", "+1d20", "+1d3", "+1d4", "+1d6", "+1d8", "+2", "+2/+4", "+20", "+2d", "+2d10", "+2d6", "+3", "+30", "+3d6", "+4", "+4/+8", "+40", "+4d6", "+5", "+50", "+5d6", "+6", "1d6", "2d10", "2020"] as $token) {
            $this->assertFalse(TextBlockService::isVocabularyToken($token, "english"), $token);
        }
    }

    public function test_vocabulary_token_classifier_preserves_valid_words(): void
    {
        foreach (["hello", "don't", "it's", "mother-in-law", "привіт", "café", "日本語"] as $token) {
            $this->assertTrue(TextBlockService::isVocabularyToken($token, "english"), $token);
        }
    }

    public function test_future_import_unique_words_and_encountered_words_skip_non_word_tokens(): void
    {
        $user = User::factory()->create();
        $service = new TextBlockService($user->id, "english");
        $service->setProcessedWords([
            $this->processedWord("Hello"),
            $this->processedWord("+1d20"),
            $this->processedWord("+1d"),
            $this->processedWord("#"),
            $this->processedWord("don't"),
        ]);

        $service->collectUniqueWords();
        $service->createNewEncounteredWords();

        $this->assertSame(["hello", "don't"], $service->uniqueWords);
        $this->assertDatabaseHas("encountered_words", ["user_id" => $user->id, "word" => "hello", "stage" => 2]);
        $this->assertDatabaseHas("encountered_words", ["user_id" => $user->id, "word" => "don't", "stage" => 2]);
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "+1d20"]);
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "+1d"]);
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "#"]);
    }

    private function processedWord(string $word): object
    {
        return (object) [
            "word" => $word,
            "lemma" => $word,
            "reading" => "",
            "lemma_reading" => "",
            "phrase_ids" => [],
        ];
    }
}
