<?php

namespace Tests\Feature;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\User;
use App\Services\GoalService;
use Carbon\Carbon;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_vocabulary_word_can_be_updated_after_creation(): void
    {
        $user = $this->createUser();
        $word = EncounteredWord::forceCreate([
            'user_id' => $user->id,
            'language' => 'spanish',
            'stage' => 2,
            'word' => 'hola',
            'lemma' => 'hola',
            'kanji' => '',
            'reading' => '',
            'base_word' => 'hola',
            'base_word_reading' => '',
            'translation' => 'hello',
            'lookup_count' => 0,
            'read_count' => 0,
            'relearning' => false,
        ]);

        $response = $this->actingAs($user)->post('/vocabulary/word/update', [
            'id' => $word->id,
            'word' => 'hola editada',
            'translation' => 'edited translation',
            'reading' => 'edited reading',
            'base_word' => 'hola base',
            'base_word_reading' => 'base reading',
            'lookup_count' => 3,
            'read_count' => 4,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('encountered_words', [
            'id' => $word->id,
            'user_id' => $user->id,
            'word' => 'hola editada',
            'translation' => 'edited translation',
            'reading' => 'edited reading',
            'base_word' => 'hola base',
            'base_word_reading' => 'base reading',
            'lookup_count' => 3,
            'read_count' => 4,
        ]);
    }

    public function test_reader_endpoint_returns_processed_chapter_without_loading_chapter_list(): void
    {
        $user = $this->createUser();
        $word = $this->createEncounteredWord($user, 'hola');
        [$book, $chapter] = $this->createProcessedChapter($user, [$word]);

        $response = $this->actingAs($user)->post('/chapters/get/reader', [
            'chapterId' => $chapter->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('bookId', $book->id)
            ->assertJsonPath('chapterId', $chapter->id)
            ->assertJsonPath('chapterName', 'Smoke chapter')
            ->assertJsonPath('language', 'spanish')
            ->assertJsonPath('wordCount', 1)
            ->assertJsonPath('words.0.word', 'hola')
            ->assertJsonPath('words.0.stage', 2)
            ->assertJsonPath('uniqueWords.0.id', $word->id);
    }

    public function test_finishing_chapter_persists_read_marker_visible_in_chapter_list(): void
    {
        $user = $this->createUser();
        $word = $this->createEncounteredWord($user, 'hola');
        [$book, $chapter] = $this->createProcessedChapter($user, [$word]);

        $finishResponse = $this->actingAs($user)->post('/chapters/finish', [
            'chapterId' => $chapter->id,
            'uniqueWords' => json_encode([]),
            'autoLevelUpWords' => false,
            'leveledUpWords' => json_encode([]),
            'leveledUpPhrases' => json_encode([]),
            'autoMoveWordsToKnown' => false,
        ]);

        $finishResponse->assertOk();
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'read_count' => 1,
        ]);

        $listResponse = $this->actingAs($user)->post('/chapters', [
            'bookId' => $book->id,
            'page' => 1,
            'perPage' => 10,
        ]);

        $listResponse->assertOk()
            ->assertJsonPath('chapters.0.id', $chapter->id)
            ->assertJsonPath('chapters.0.read_count', 1)
            ->assertJsonPath('chapters.0.wordCount.total', 1)
            ->assertJsonPath('total', 1);
    }

    public function test_review_flow_returns_due_word_and_persists_correct_and_missed_srs_changes(): void
    {
        $user = $this->createUser();
        $word = $this->createEncounteredWord($user, 'hola', [
            'stage' => -3,
            'translation' => 'hello',
            'next_review' => Carbon::today()->toDateString(),
        ]);
        [$book, $chapter] = $this->createProcessedChapter($user, [$word]);

        $reviewResponse = $this->actingAs($user)->post('/reviews', [
            'practiceMode' => false,
            'chapterId' => $chapter->id,
            'bookId' => $book->id,
        ]);

        $reviewResponse->assertOk()
            ->assertJsonPath('reviews.0.id', $word->id)
            ->assertJsonPath('reviews.0.type', 'word')
            ->assertJsonPath('reviews.0.stage', -3);

        $correctResponse = $this->actingAs($user)->post('/vocabulary/word/update', [
            'id' => $word->id,
            'stage' => -2,
            'savedDuringReview' => true,
        ]);

        $correctResponse->assertOk();
        $this->assertDatabaseHas('encountered_words', [
            'id' => $word->id,
            'stage' => -2,
            'relearning' => false,
        ]);

        $missedResponse = $this->actingAs($user)->post('/vocabulary/word/update', [
            'id' => $word->id,
            'stage' => -3,
            'relearning' => true,
            'savedDuringReview' => true,
        ]);

        $missedResponse->assertOk();
        $this->assertDatabaseHas('encountered_words', [
            'id' => $word->id,
            'stage' => -3,
            'relearning' => true,
        ]);
    }

    private function createUser(): User
    {
        $this->seed(SettingsSeeder::class);

        $user = User::factory()->create([
            'selected_language' => 'spanish',
            'uuid' => Str::uuid()->toString(),
        ]);

        (new GoalService())->createGoalsForLanguage($user->id, 'spanish');

        return $user;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createEncounteredWord(User $user, string $word, array $overrides = []): EncounteredWord
    {
        return EncounteredWord::forceCreate(array_merge([
            'user_id' => $user->id,
            'language' => 'spanish',
            'stage' => 2,
            'word' => $word,
            'lemma' => $word,
            'kanji' => '',
            'reading' => '',
            'base_word' => $word,
            'base_word_reading' => '',
            'translation' => '',
            'lookup_count' => 0,
            'read_count' => 0,
            'relearning' => false,
        ], $overrides));
    }

    /**
     * @param array<int, EncounteredWord> $words
     * @return array{0: Book, 1: Chapter}
     */
    private function createProcessedChapter(User $user, array $words): array
    {
        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Smoke book',
            'cover_image' => '',
            'language' => 'spanish',
        ]);

        $processedWords = [];
        foreach ($words as $index => $word) {
            $processedWord = new \stdClass();
            $processedWord->user_id = $user->id;
            $processedWord->word_index = $index;
            $processedWord->sentence_index = 0;
            $processedWord->word = $word->word;
            $processedWord->lemma = $word->word;
            $processedWord->reading = '';
            $processedWord->lemma_reading = '';
            $processedWord->pos = 'NOUN';
            $processedWord->phrase_ids = [];
            $processedWords[] = $processedWord;
        }

        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->read_count = 0;
        $chapter->word_count = count($words);
        $chapter->name = 'Smoke chapter';
        $chapter->language = 'spanish';
        $chapter->raw_text = implode(' ', array_map(fn (EncounteredWord $word) => $word->word, $words));
        $chapter->unique_words = json_encode(array_map(fn (EncounteredWord $word) => $word->word, $words));
        $chapter->unique_word_ids = json_encode(array_map(fn (EncounteredWord $word) => $word->id, $words));
        $chapter->unique_phrase_ids = json_encode([]);
        $chapter->type = 'text';
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->setProcessedText($processedWords);
        $chapter->save();

        return [$book, $chapter];
    }
}
