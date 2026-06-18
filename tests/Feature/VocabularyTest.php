<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Models\Book;
use App\Models\Chapter;
use App\Enums\ChapterProcessingStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VocabularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_update_spelling_and_properties_success(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'japanese';
        $user->save();

        $word = EncounteredWord::forceCreate([
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'kanji' => '',
            'word' => 'testword',
            'base_word' => 'testword',
            'base_word_reading' => '',
            'lemma' => '',
            'translation' => 'old translation',
            'reading' => 'old reading'
        ]);

        $response = $this->actingAs($user)->postJson('/vocabulary/word/update', [
            'id' => $word->id,
            'word' => 'updatedword',
            'translation' => 'new translation',
            'reading' => 'new reading'
        ]);

        $response->assertStatus(200);

        $word->refresh();
        $this->assertEquals('updatedword', $word->word);
        $this->assertEquals('new translation', $word->translation);
        $this->assertEquals('new reading', $word->reading);
    }

    public function test_word_update_duplicate_fails(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'japanese';
        $user->save();

        $word1 = EncounteredWord::forceCreate([
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'word' => 'apple',
            'base_word' => 'apple',
            'base_word_reading' => '',
            'kanji' => '',
            'reading' => '',
            'lemma' => '',
            'translation' => ''
        ]);

        $word2 = EncounteredWord::forceCreate([
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'word' => 'banana',
            'base_word' => 'banana',
            'base_word_reading' => '',
            'kanji' => '',
            'reading' => '',
            'lemma' => '',
            'translation' => ''
        ]);

        $response = $this->actingAs($user)->postJson('/vocabulary/word/update', [
            'id' => $word2->id,
            'word' => 'apple'
        ]);

        $response->assertStatus(500);
        $word2->refresh();
        $this->assertEquals('banana', $word2->word);
    }

    public function test_phrase_update_spelling_and_properties_success(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'english';
        $user->save();

        $phrase = Phrase::forceCreate([
            'user_id' => $user->id,
            'language' => 'english',
            'words' => json_encode(['how', 'are', 'you']),
            'words_searchable' => 'how are you',
            'reading' => '',
            'translation' => 'old translation',
            'stage' => 2
        ]);

        $response = $this->actingAs($user)->postJson('/vocabulary/phrases/update', [
            'id' => $phrase->id,
            'words' => 'how goes it',
            'translation' => 'new translation'
        ]);

        $response->assertStatus(200);

        $phrase->refresh();
        $this->assertEquals('how are you', $phrase->words_searchable);
        $this->assertEquals(json_encode(['how', 'are', 'you']), $phrase->words);
        $this->assertEquals('new translation', $phrase->translation);
    }



    public function test_phrase_create_accepts_hyphenated_words_and_rejects_invalid_tokens(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'english';
        $user->save();

        $validResponse = $this->actingAs($user)->postJson('/vocabulary/phrases/create', [
            'words' => json_encode(['go', 'stir-crazy']),
            'stage' => 2,
            'reading' => '',
            'translation' => '',
        ]);

        $validResponse->assertStatus(200);
        $this->assertDatabaseHas('phrases', [
            'user_id' => $user->id,
            'words_searchable' => 'go stir-crazy',
        ]);

        foreach ([['#'], ['+1'], ['+10d6'], ['+2/+4'], ['):' ], ["'s"]] as $words) {
            $response = $this->actingAs($user)->postJson('/vocabulary/phrases/create', [
                'words' => json_encode($words),
                'stage' => 2,
                'reading' => '',
                'translation' => '',
            ]);

            $response->assertStatus(500);
        }
    }
    public function test_phrase_update_ignores_words_payload(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'english';
        $user->save();

        $phrase1 = Phrase::forceCreate([
            'user_id' => $user->id,
            'language' => 'english',
            'words' => json_encode(['hello', 'world']),
            'words_searchable' => 'hello world',
            'reading' => '',
            'translation' => '',
            'stage' => 2
        ]);

        $phrase2 = Phrase::forceCreate([
            'user_id' => $user->id,
            'language' => 'english',
            'words' => json_encode(['hello', 'there']),
            'words_searchable' => 'hello there',
            'reading' => '',
            'translation' => '',
            'stage' => 2
        ]);

        $response = $this->actingAs($user)->postJson('/vocabulary/phrases/update', [
            'id' => $phrase2->id,
            'words' => 'hello world'
        ]);

        $response->assertStatus(200);
        $phrase2->refresh();
        $this->assertEquals('hello there', $phrase2->words_searchable);
    }
    public function test_book_filter_uses_chapter_word_ids_instead_of_word_text(): void
    {
        $user = User::factory()->create();
        $user->selected_language = "english";
        $user->save();

        $firstBook = Book::create([
            "user_id" => $user->id,
            "name" => "First Book",
            "language" => "english",
        ]);
        $secondBook = Book::create([
            "user_id" => $user->id,
            "name" => "Second Book",
            "language" => "english",
        ]);

        $firstWord = EncounteredWord::forceCreate([
            "id" => 101,
            "user_id" => $user->id,
            "language" => "english",
            "stage" => 2,
            "word" => "shared",
            "base_word" => "shared",
            "base_word_reading" => "",
            "kanji" => "",
            "reading" => "",
            "lemma" => "",
            "translation" => "",
        ]);
        EncounteredWord::forceCreate([
            "id" => 102,
            "user_id" => $user->id,
            "language" => "english",
            "stage" => 2,
            "word" => "shared",
            "base_word" => "shared",
            "base_word_reading" => "",
            "kanji" => "",
            "reading" => "",
            "lemma" => "",
            "translation" => "",
        ]);

        foreach ([[$firstBook->id, [101]], [$secondBook->id, [102]]] as [$bookId, $wordIds]) {
            $chapter = new Chapter();
            $chapter->user_id = $user->id;
            $chapter->book_id = $bookId;
            $chapter->name = "Chapter";
            $chapter->read_count = 0;
            $chapter->word_count = 1;
            $chapter->language = "english";
            $chapter->raw_text = "shared";
            $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
            $chapter->unique_words = json_encode(["shared"]);
            $chapter->unique_word_ids = json_encode($wordIds);
            $chapter->unique_phrase_ids = json_encode([]);
            $chapter->subtitle_timestamps = json_encode([]);
            $chapter->save();
        }

        $response = $this->actingAs($user)->postJson("/vocabulary/search", [
            "text" => "anytext",
            "book" => $firstBook->id,
            "chapter" => -1,
            "stage" => -999,
            "phrases" => "only words",
            "orderBy" => "words",
            "translation" => "any",
            "page" => 1,
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json("wordCount"));
        $this->assertSame($firstWord->id, $response->json("words.0.id"));
    }

    public function test_book_filter_falls_back_when_unique_word_ids_are_missing_or_empty(): void
    {
        $user = $this->createVocabularyUser("english");
        $book = $this->createVocabularyBook($user, "Migrated Book");

        $alpha = $this->createVocabularyWord($user, "alpha");
        $beta = $this->createVocabularyWord($user, "beta");
        $this->createVocabularyWord($user, "outside");

        $this->createVocabularyChapter($user, $book, ["alpha"], null);
        $this->createVocabularyChapter($user, $book, ["beta"], []);

        $response = $this->searchVocabularyWords($user, $book->id, -1);

        $response->assertStatus(200);
        $this->assertSame(2, $response->json("wordCount"));
        $this->assertEqualsCanonicalizing([$alpha->id, $beta->id], $this->responseWordIds($response));
    }

    public function test_book_filter_falls_back_when_unique_word_ids_are_stale_or_mismatched(): void
    {
        $user = $this->createVocabularyUser("english");
        $book = $this->createVocabularyBook($user, "Stale Id Book");

        $expectedWord = $this->createVocabularyWord($user, "alpha");
        $mismatchedWord = $this->createVocabularyWord($user, "wrong");
        $this->createVocabularyWord($user, "outside");

        $this->createVocabularyChapter($user, $book, ["alpha"], [$mismatchedWord->id]);

        $response = $this->searchVocabularyWords($user, $book->id, -1);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json("wordCount"));
        $this->assertSame([$expectedWord->id], $this->responseWordIds($response));
    }

    public function test_book_filter_returns_distinct_words_and_phrases_for_each_book_and_any(): void
    {
        $user = $this->createVocabularyUser("english");
        $firstBook = $this->createVocabularyBook($user, "Silo");
        $secondBook = $this->createVocabularyBook($user, "Candela Obscure");
        $firstWord = $this->createVocabularyWord($user, "silo");
        $secondWord = $this->createVocabularyWord($user, "candela");
        $firstPhrase = Phrase::forceCreate([
            "user_id" => $user->id,
            "language" => "english",
            "words" => json_encode(["underground", "silo"]),
            "words_searchable" => "underground silo",
            "reading" => "",
            "translation" => "",
            "stage" => 2,
        ]);
        $secondPhrase = Phrase::forceCreate([
            "user_id" => $user->id,
            "language" => "english",
            "words" => json_encode(["candela", "obscura"]),
            "words_searchable" => "candela obscura",
            "reading" => "",
            "translation" => "",
            "stage" => 2,
        ]);

        $firstChapter = $this->createVocabularyChapter($user, $firstBook, [$firstWord->word], [$firstWord->id]);
        $firstChapter->unique_phrase_ids = json_encode([$firstPhrase->id]);
        $firstChapter->save();
        $secondChapter = $this->createVocabularyChapter($user, $secondBook, [$secondWord->word], [$secondWord->id]);
        $secondChapter->unique_phrase_ids = json_encode([$secondPhrase->id]);
        $secondChapter->save();

        $firstResponse = $this->searchVocabulary($user, $firstBook->id, -1, "words and phrases");
        $secondResponse = $this->searchVocabulary($user, $secondBook->id, -1, "words and phrases");
        $anyResponse = $this->searchVocabulary($user, -1, -1, "words and phrases");

        $firstResponse->assertOk();
        $secondResponse->assertOk();
        $anyResponse->assertOk();
        $this->assertEqualsCanonicalizing(
            [["id" => $firstWord->id, "type" => "word"], ["id" => $firstPhrase->id, "type" => "phrase"]],
            $this->responseVocabularyKeys($firstResponse)
        );
        $this->assertEqualsCanonicalizing(
            [["id" => $secondWord->id, "type" => "word"], ["id" => $secondPhrase->id, "type" => "phrase"]],
            $this->responseVocabularyKeys($secondResponse)
        );
        $this->assertEqualsCanonicalizing(
            [
                ["id" => $firstWord->id, "type" => "word"],
                ["id" => $secondWord->id, "type" => "word"],
                ["id" => $firstPhrase->id, "type" => "phrase"],
                ["id" => $secondPhrase->id, "type" => "phrase"],
            ],
            $this->responseVocabularyKeys($anyResponse)
        );
    }

    public function test_book_filter_includes_words_from_partially_migrated_chapters(): void
    {
        $user = $this->createVocabularyUser("english");
        $book = $this->createVocabularyBook($user, "Partially Migrated Book");
        $idBackedWord = $this->createVocabularyWord($user, "modern");
        $legacyWord = $this->createVocabularyWord($user, "legacy");
        $this->createVocabularyWord($user, "outside");

        $this->createVocabularyChapter($user, $book, [$idBackedWord->word], [$idBackedWord->id]);
        $this->createVocabularyChapter($user, $book, [$legacyWord->word], null);

        $response = $this->searchVocabularyWords($user, $book->id, -1);

        $response->assertOk();
        $this->assertSame(2, $response->json("wordCount"));
        $this->assertEqualsCanonicalizing([$idBackedWord->id, $legacyWord->id], $this->responseWordIds($response));
    }

    public function test_book_filter_falls_back_to_processed_text_when_phrase_ids_are_missing(): void
    {
        $user = $this->createVocabularyUser("english");
        $book = $this->createVocabularyBook($user, "Legacy Phrase Book");
        $phrase = Phrase::forceCreate([
            "user_id" => $user->id,
            "language" => "english",
            "words" => json_encode(["legacy", "phrase"]),
            "words_searchable" => "legacy phrase",
            "reading" => "",
            "translation" => "",
            "stage" => 2,
        ]);
        $chapter = $this->createVocabularyChapter($user, $book, [], []);
        $chapter->unique_phrase_ids = null;
        $chapter->setProcessedText([(object) ["w" => "legacy", "phrase_ids" => [$phrase->id]]]);
        $chapter->save();

        $response = $this->searchVocabulary($user, $book->id, -1, "only phrases");

        $response->assertOk();
        $this->assertSame(1, $response->json("wordCount"));
        $this->assertSame([["id" => $phrase->id, "type" => "phrase"]], $this->responseVocabularyKeys($response));
    }

    public function test_chapter_filter_matches_book_filter_for_migrated_unique_words_fallback(): void
    {
        $user = $this->createVocabularyUser("english");
        $book = $this->createVocabularyBook($user, "Chapter Filter Book");

        $chapterWord = $this->createVocabularyWord($user, "chapterword");
        $this->createVocabularyWord($user, "otherchapterword");

        $chapter = $this->createVocabularyChapter($user, $book, ["chapterword"], null);
        $this->createVocabularyChapter($user, $book, ["otherchapterword"], null);

        $response = $this->searchVocabularyWords($user, -1, $chapter->id);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json("wordCount"));
        $this->assertSame([$chapterWord->id], $this->responseWordIds($response));
    }

    public function test_vocabulary_search_filters_existing_invalid_non_word_tokens(): void
    {
        $user = $this->createVocabularyUser("english");
        $valid = $this->createVocabularyWord($user, "don't");
        $hyphenated = $this->createVocabularyWord($user, "mother-in-law");
        $unicode = $this->createVocabularyWord($user, "привіт");
        $this->createVocabularyWord($user, "+1d", ["stage" => 2]);
        $this->createVocabularyWord($user, "+2/+4", ["stage" => -1]);
        $this->createVocabularyWord($user, "#", ["stage" => 0]);
        foreach (["-fis", "-m", "/12*mp", "1+db", "1b", "1d10+db", "1d3+db", "1d3-1", "1d4+poison", "1d6/"] as $token) {
            $this->createVocabularyWord($user, $token, ["stage" => 2]);
        }

        $response = $this->searchVocabularyWords($user, -1, -1);

        $response->assertStatus(200);
        $this->assertSame(3, $response->json("wordCount"));
        $this->assertEqualsCanonicalizing(
            [$valid->id, $hyphenated->id, $unicode->id],
            $this->responseWordIds($response)
        );
    }

    public function test_vocabulary_csv_import_rejects_invalid_tokens_and_keeps_valid_words(): void
    {
        $user = $this->createVocabularyUser("english");
        $fileName = "vocabulary-import-test.csv";
        $path = storage_path("app/temp/" . $fileName);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, implode("\n", [
            "word|translation",
            "stir-crazy|valid",
            "1d6+db|invalid",
            "-fis|invalid",
        ]));

        try {
            $service = app(\App\Services\VocabularyService::class);
            $result = $service->importFromCsv($user->id, "english", $fileName, "|", false, true);
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $this->assertSame(1, $result->createdWords);
        $this->assertSame(2, $result->rejectedWords);
        $this->assertDatabaseHas("encountered_words", ["user_id" => $user->id, "word" => "stir-crazy"]);
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "1d6+db"]);
        $this->assertDatabaseMissing("encountered_words", ["user_id" => $user->id, "word" => "-fis"]);
    }

    private function createVocabularyUser(string $language): User
    {
        $user = User::factory()->create();
        $user->selected_language = $language;
        $user->save();

        return $user;
    }

    private function createVocabularyBook(User $user, string $name): Book
    {
        return Book::create([
            "user_id" => $user->id,
            "name" => $name,
            "language" => $user->selected_language,
        ]);
    }

    private function createVocabularyWord(User $user, string $word, array $attributes = []): EncounteredWord
    {
        return EncounteredWord::forceCreate(array_merge([
            "user_id" => $user->id,
            "language" => $user->selected_language,
            "stage" => 2,
            "word" => $word,
            "base_word" => $word,
            "base_word_reading" => "",
            "kanji" => "",
            "reading" => "",
            "lemma" => "",
            "translation" => "",
        ], $attributes));
    }

    private function createVocabularyChapter(User $user, Book $book, array $uniqueWords, ?array $uniqueWordIds): Chapter
    {
        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->name = "Chapter";
        $chapter->read_count = 0;
        $chapter->word_count = count($uniqueWords);
        $chapter->language = $user->selected_language;
        $chapter->raw_text = implode(" ", $uniqueWords);
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->unique_words = json_encode($uniqueWords);
        $chapter->unique_word_ids = $uniqueWordIds === null ? null : json_encode($uniqueWordIds);
        $chapter->unique_phrase_ids = json_encode([]);
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->save();

        return $chapter;
    }

    private function searchVocabularyWords(User $user, int $bookId, int $chapterId)
    {
        return $this->searchVocabulary($user, $bookId, $chapterId, "only words");
    }

    private function searchVocabulary(User $user, int $bookId, int $chapterId, string $phrases)
    {
        return $this->actingAs($user)->postJson("/vocabulary/search", [
            "text" => "anytext",
            "book" => $bookId,
            "chapter" => $chapterId,
            "stage" => -999,
            "phrases" => $phrases,
            "orderBy" => "words",
            "translation" => "any",
            "page" => 1,
        ]);
    }

    private function responseWordIds($response): array
    {
        return array_column($response->json("words"), "id");
    }

    private function responseVocabularyKeys($response): array
    {
        return array_map(function(array $item) {
            return [
                "id" => $item["id"],
                "type" => $item["type"],
            ];
        }, $response->json("words"));
    }

}
