<?php

namespace Tests\Feature;

use App\Models\Phrase;
use App\Models\User;
use App\Services\TextBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextBlockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_vocabulary_token_classifier_rejects_reported_non_word_tokens(): void
    {
        foreach (["#", "'s", "):", "+1", "+10", "+10d6", "+1d", "+1d10", "+1d20", "+1d3", "+1d4", "+1d6", "+1d8", "+2", "+2/+4", "+20", "+2d", "+2d10", "+2d6", "+3", "+30", "+3d6", "+4", "+4/+8", "+40", "+4d6", "+5", "+50", "+5d6", "+6", "1d6", "2d10", "2020", "---", "-", "well-", "-known"] as $token) {
            $this->assertFalse(TextBlockService::isVocabularyToken($token, "english"), $token);
        }
    }

    public function test_vocabulary_token_classifier_preserves_valid_words(): void
    {
        foreach (["hello", "don't", "it's", "mother-in-law", "stir-crazy", "well-known", "state-of-the-art", "rock’n’roll", "привіт", "café", "日本語"] as $token) {
            $this->assertTrue(TextBlockService::isVocabularyToken($token, "english"), $token);
        }
    }


    public function test_process_tokenized_words_selects_hyphenated_word_as_one_reader_word(): void
    {
        $user = User::factory()->create();
        $service = new TextBlockService($user->id, "english");
        $service->tokenizedWords = [
            $this->tokenizedWord("stir"),
            $this->tokenizedWord("-", "PUNCT"),
            $this->tokenizedWord("crazy"),
        ];

        $service->processTokenizedWords();
        $service->collectUniqueWords();
        $service->createNewEncounteredWords();
        $service->prepareTextForReader();

        $this->assertSame(["stir-crazy"], array_map(fn ($word) => $word->word, $service->processedWords));
        $this->assertSame("stir-crazy", $service->getReaderData()->words[0]->word);
        $this->assertDatabaseHas("encountered_words", ["user_id" => $user->id, "word" => "stir-crazy", "stage" => 2]);
    }

    public function test_process_tokenized_words_keeps_hyphenated_word_next_to_punctuation(): void
    {
        $user = User::factory()->create();
        $service = new TextBlockService($user->id, "english");
        $service->tokenizedWords = [
            $this->tokenizedWord("well"),
            $this->tokenizedWord("-", "PUNCT"),
            $this->tokenizedWord("known"),
            $this->tokenizedWord(",", "PUNCT"),
        ];

        $service->processTokenizedWords();
        $service->collectUniqueWords();

        $this->assertSame(["well-known", ","], array_map(fn ($word) => $word->word, $service->processedWords));
        $this->assertSame(["well-known"], $service->uniqueWords);
    }

    public function test_process_tokenized_words_selects_phrase_containing_hyphenated_word(): void
    {
        $user = User::factory()->create();
        $service = new TextBlockService($user->id, "english");
        $service->tokenizedWords = [
            $this->tokenizedWord("go"),
            $this->tokenizedWord("stir"),
            $this->tokenizedWord("-", "PUNCT"),
            $this->tokenizedWord("crazy"),
        ];

        $service->processTokenizedWords();
        $service->collectUniqueWords();
        $phrase = Phrase::forceCreate([
            "user_id" => $user->id,
            "language" => "english",
            "words" => json_encode(["go", "stir-crazy"]),
            "words_searchable" => "go stir-crazy",
            "reading" => "",
            "translation" => "",
            "stage" => 2,
        ]);

        $this->assertTrue($service->updatePhraseIds($phrase));
        $this->assertSame(["go", "stir-crazy"], array_map(fn ($word) => $word->word, $service->processedWords));
        $this->assertSame([$phrase->id], $service->processedWords[0]->phrase_ids);
        $this->assertSame([$phrase->id], $service->processedWords[1]->phrase_ids);
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


    private function tokenizedWord(string $word, string $pos = "NOUN"): object
    {
        return (object) [
            "w" => $word,
            "l" => mb_strtolower($word, "UTF-8"),
            "si" => 0,
            "pos" => $pos,
            "g" => [],
        ];
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
