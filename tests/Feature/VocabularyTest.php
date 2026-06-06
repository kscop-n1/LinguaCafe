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
        return $this->actingAs($user)->postJson("/vocabulary/search", [
            "text" => "anytext",
            "book" => $bookId,
            "chapter" => $chapterId,
            "stage" => -999,
            "phrases" => "only words",
            "orderBy" => "words",
            "translation" => "any",
            "page" => 1,
        ]);
    }

    private function responseWordIds($response): array
    {
        return array_column($response->json("words"), "id");
    }

}
