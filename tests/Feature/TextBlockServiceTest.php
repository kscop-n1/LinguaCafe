<?php

namespace Tests\Feature;

use App\Models\Phrase;
use App\Models\EncounteredWord;
use App\Models\User;
use App\Services\TextBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextBlockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_vocabulary_token_classifier_rejects_reported_non_word_tokens(): void
    {
        foreach (["#", "'s", "):", "+1", "+10", "+10d6", "+1d", "+1d10", "+1d20", "+1d3", "+1d4", "+1d6", "+1d8", "+2", "+2/+4", "+20", "+2d", "+2d10", "+2d6", "+3", "+30", "+3d6", "+4", "+4/+8", "+40", "+4d6", "+5", "+50", "+5d6", "+6", "1d6", "2d10", "2020", "---", "-", "well-", "-known", "-fis", "-m", "/12*mp", "1+db", "1b", "1d10+db", "1d3+db", "1d3-1", "1d4+db", "1d4+poison", "1d6+db", "1d6/", "1d8+db"] as $token) {
            $decision = TextBlockService::classifyVocabularyToken($token, "english");
            $this->assertFalse($decision["valid"], $token);
            $this->assertNotSame("unknown_suspicious_token", $decision["reason"], $token);
        }
    }

    public function test_vocabulary_token_classifier_preserves_valid_words(): void
    {
        foreach (["hello", "don't", "it's", "mother-in-law", "stir-crazy", "well-known", "state-of-the-art", "rock’n’roll", "привіт", "café", "日本語"] as $token) {
            $this->assertTrue(TextBlockService::isVocabularyToken($token, "english"), $token);
        }
    }

    public function test_vocabulary_token_classifier_returns_structured_rejection_reasons(): void
    {
        $examples = [
            "#" => "punctuation_only",
            "'s" => "standalone_apostrophe_suffix",
            "+1" => "signed_number",
            "2020" => "number_only",
            "+10d6" => "dice_notation",
            "1d4+poison" => "dice_stat_arithmetic",
            "1d6/" => "trailing_punctuation_fragment",
            "+2/+4" => "slash_star_math_expression",
            "-fis" => "leading_punctuation_fragment",
            "/12*mp" => "slash_star_math_expression",
            "1b" => "number_letter_mixture",
            "abc_def" => "unknown_suspicious_token",
            "́word" => "unknown_suspicious_token",
            "́" => "unknown_suspicious_token",
        ];

        foreach ($examples as $token => $reason) {
            $decision = TextBlockService::classifyVocabularyToken($token, "english");

            $this->assertFalse($decision["valid"], $token);
            $this->assertSame($reason, $decision["reason"], $token);
            $this->assertArrayHasKey("ambiguous", $decision, $token);
        }

        foreach (["hello", "don't", "mother-in-law", "привіт", "café", "日本語"] as $token) {
            $decision = TextBlockService::classifyVocabularyToken($token, "english");

            $this->assertTrue($decision["valid"], $token);
            $this->assertSame("valid_lexical_token", $decision["reason"], $token);
            $this->assertFalse($decision["ambiguous"], $token);
        }
    }

    public function test_valid_vocabulary_scope_matches_classifier_for_unicode_and_reported_tokens(): void
    {
        $user = User::factory()->create();
        $validTokens = [
            "hello",
            "don't",
            "mother-in-law",
            "привіт",
            "café",
            "café",
            "日本語",
        ];
        $invalidTokens = [
            "#",
            "'s",
            "):",
            "+1",
            "+10d6",
            "+2/+4",
            "1d10+db",
            "1d4+poison",
            "1d6/",
            "-fis",
            "-m",
            "/12*mp",
        ];

        foreach (array_merge($validTokens, $invalidTokens) as $token) {
            EncounteredWord::forceCreate([
                "user_id" => $user->id,
                "language" => "english",
                "stage" => 2,
                "word" => $token,
                "base_word" => "",
                "base_word_reading" => "",
                "kanji" => "",
                "reading" => "",
                "lemma" => "",
                "translation" => "",
            ]);
        }

        $scopeTokens = EncounteredWord::query()
            ->where("user_id", $user->id)
            ->validVocabularyToken()
            ->pluck("word")
            ->toArray();

        $this->assertEqualsCanonicalizing($validTokens, $scopeTokens);
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
            $this->processedWord("1d6+db"),
            $this->processedWord("-fis"),
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
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "1d6+db"]);
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "-fis"]);
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
