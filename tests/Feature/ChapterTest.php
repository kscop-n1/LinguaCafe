<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Enums\ChapterProcessingStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapters_endpoint_requires_auth(): void
    {
        $response = $this->postJson('/chapters', [
            'bookId' => 1
        ]);

        $response->assertStatus(401);
    }

    public function test_chapters_endpoint_validates_input(): void
    {
        $user = User::factory()->create();

        // bookId is required and must exist
        $response = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => 9999
        ]);
        $response->assertStatus(422);

        // create book to validate bookId exists
        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Test Book',
            'language' => 'japanese'
        ]);

        // invalid page
        $response = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'page' => -1
        ]);
        $response->assertStatus(422);

        // invalid perPage
        $response = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'perPage' => 1000
        ]);
        $response->assertStatus(422);
    }

    public function test_chapters_endpoint_paginates_results(): void
    {
        $user = User::factory()->create();
        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Test Book',
            'language' => 'japanese'
        ]);

        // Create 5 chapters
        for ($i = 1; $i <= 5; $i++) {
            $chapter = new Chapter();
            $chapter->user_id = $user->id;
            $chapter->book_id = $book->id;
            $chapter->name = "Chapter {$i}";
            $chapter->read_count = 0;
            $chapter->word_count = 100;
            $chapter->language = 'japanese';
            $chapter->raw_text = 'raw text';
            $chapter->processing_status = ChapterProcessingStatusEnum::UNPROCESSED->value;
            $chapter->unique_words = json_encode([]);
            $chapter->subtitle_timestamps = json_encode([]);
            $chapter->unique_word_ids = json_encode([]);
            $chapter->save();
        }

        // Get page 1 (2 items per page)
        $response = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'page' => 1,
            'perPage' => 2
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('currentPage', 1);
        $response->assertJsonPath('total', 5);
        $this->assertCount(2, $response->json('chapters'));

        // Get page 3 (2 items per page, should have 1 item)
        $response = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'page' => 3,
            'perPage' => 2
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('currentPage', 3);
        $this->assertCount(1, $response->json('chapters'));
    }

    public function test_chapters_endpoint_calculates_word_counts(): void
    {
        $user = User::factory()->create();
        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Test Book',
            'language' => 'japanese'
        ]);

        // Create a processed chapter
        $chapter = new Chapter();
        $chapter->user_id = $user->id;
        $chapter->book_id = $book->id;
        $chapter->name = "Chapter 1";
        $chapter->read_count = 0;
        $chapter->word_count = 100;
        $chapter->language = 'japanese';
        $chapter->raw_text = 'raw text';
        $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
        $chapter->unique_words = json_encode(["highlightedword", "knownword", "newword"]);
        $chapter->subtitle_timestamps = json_encode([]);
        $chapter->unique_word_ids = json_encode([10, 20, 30]);
        $chapter->save();

        // Create encountered words
        // Word 10: highlighted (stage < 0, e.g. -1)
        // Word 20: known (stage == 0)
        // Word 30: new (stage == 2)
        EncounteredWord::forceCreate([
            'id' => 10,
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => -1,
            'word' => 'highlightedword',
            'kanji' => '',
            'reading' => '',
            'base_word' => 'highlightedword',
            'base_word_reading' => '',
            'lemma' => '',
            'translation' => ''
        ]);
        EncounteredWord::forceCreate([
            'id' => 20,
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 0,
            'word' => 'knownword',
            'kanji' => '',
            'reading' => '',
            'base_word' => 'knownword',
            'base_word_reading' => '',
            'lemma' => '',
            'translation' => ''
        ]);
        EncounteredWord::forceCreate([
            'id' => 30,
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'word' => 'newword',
            'kanji' => '',
            'reading' => '',
            'base_word' => 'newword',
            'base_word_reading' => '',
            'lemma' => '',
            'translation' => ''
        ]);

        $response = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'page' => 1,
            'perPage' => 10
        ]);

        $response->assertStatus(200);
        $chaptersData = $response->json('chapters');
        $this->assertCount(1, $chaptersData);

        $wordCount = $chaptersData[0]['wordCount'];
        $this->assertEquals(100, $wordCount['total']);
        $this->assertEquals(3, $wordCount['unique']);
        $this->assertEquals(1, $wordCount['highlighted']);
        $this->assertEquals(1, $wordCount['known']);
        $this->assertEquals(1, $wordCount['new']);
    }

    public function test_chapters_endpoint_keeps_total_and_statistics_stable_for_large_books(): void
    {
        $user = User::factory()->create(['selected_language' => 'japanese']);
        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Large Test Book',
            'language' => 'japanese',
        ]);

        $chapters = [];

        for ($i = 1; $i <= 83; $i++) {
            $stage = $i % 3 === 0 ? -1 : ($i % 3 === 1 ? 0 : 2);
            $wordText = 'word' . str_repeat('a', $i);
            $word = EncounteredWord::forceCreate([
                'user_id' => $user->id,
                'language' => 'japanese',
                'stage' => $stage,
                'word' => $wordText,
                'kanji' => '',
                'reading' => '',
                'base_word' => $wordText,
                'base_word_reading' => '',
                'lemma' => '',
                'translation' => '',
            ]);

            $chapter = new Chapter();
            $chapter->user_id = $user->id;
            $chapter->book_id = $book->id;
            $chapter->name = sprintf('Chapter %02d', $i);
            $chapter->read_count = 0;
            $chapter->word_count = $i === 83 ? 0 : 10;
            $chapter->language = 'japanese';
            $chapter->raw_text = 'raw text';
            $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
            $chapter->unique_words = json_encode([$word->word]);
            $chapter->subtitle_timestamps = json_encode([]);
            $chapter->unique_word_ids = json_encode([$word->id]);
            $chapter->save();

            $chapters[] = $chapter;
        }

        foreach ([10, 25, 50] as $perPage) {
            $response = $this->actingAs($user)->postJson('/chapters', [
                'bookId' => $book->id,
                'page' => 1,
                'perPage' => $perPage,
            ]);

            $response->assertOk()
                ->assertJsonPath('currentPage', 1)
                ->assertJsonPath('perPage', $perPage)
                ->assertJsonPath('total', 83);
            $this->assertCount($perPage, $response->json('chapters'));
        }

        $pageTwoResponse = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'page' => 2,
            'perPage' => 50,
        ]);

        $pageTwoResponse->assertOk()
            ->assertJsonPath('currentPage', 2)
            ->assertJsonPath('total', 83);
        $this->assertCount(33, $pageTwoResponse->json('chapters'));
        $this->assertSame($chapters[50]->id, $pageTwoResponse->json('chapters.0.id'));
        $this->assertSame(10, $pageTwoResponse->json('chapters.9.wordCount.total'));
        $this->assertSame(1, $pageTwoResponse->json('chapters.9.wordCount.unique'));
        $this->assertIsInt($pageTwoResponse->json('chapters.9.wordCount.known'));
        $this->assertIsInt($pageTwoResponse->json('chapters.9.wordCount.highlighted'));
        $this->assertIsInt($pageTwoResponse->json('chapters.9.wordCount.new'));
        foreach ($pageTwoResponse->json('chapters') as $chapterData) {
            $this->assertTrue($chapterData['wordCountsLoaded']);
            foreach (['total', 'unique', 'known', 'highlighted', 'new'] as $statistic) {
                $this->assertArrayHasKey($statistic, $chapterData['wordCount']);
                $this->assertIsInt($chapterData['wordCount'][$statistic]);
            }
        }

        $allResponse = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'page' => 1,
            'all' => true,
        ]);

        $allResponse->assertOk()
            ->assertJsonPath('currentPage', 1)
            ->assertJsonPath('lastPage', 1)
            ->assertJsonPath('perPage', 83)
            ->assertJsonPath('total', 83)
            ->assertJsonPath('chapters.82.wordCount.total', 0)
            ->assertJsonPath('chapters.82.wordCount.unique', 1)
            ->assertJsonPath('chapters.82.wordCount.known', 0)
            ->assertJsonPath('chapters.82.wordCount.highlighted', 0)
            ->assertJsonPath('chapters.82.wordCount.new', 1)
            ->assertJsonPath('chapters.82.wordCountsLoaded', true);
        $this->assertCount(83, $allResponse->json('chapters'));

        $invalidResponse = $this->actingAs($user)->postJson('/chapters', [
            'bookId' => $book->id,
            'perPage' => -1,
        ]);

        $invalidResponse->assertStatus(422);
    }

    public function test_reader_endpoint_resolves_next_chapter_id(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'japanese';
        $user->save();

        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Test Book',
            'language' => 'japanese'
        ]);

        // Create 3 processed chapters
        $chapters = [];
        for ($i = 1; $i <= 3; $i++) {
            $chapter = new Chapter();
            $chapter->user_id = $user->id;
            $chapter->book_id = $book->id;
            $chapter->name = "Chapter {$i}";
            $chapter->read_count = 0;
            $chapter->word_count = 50;
            $chapter->language = 'japanese';
            $chapter->raw_text = 'raw text';
            $chapter->processing_status = ChapterProcessingStatusEnum::PROCESSED->value;
            $chapter->unique_words = json_encode([]);
            $chapter->subtitle_timestamps = json_encode([]);
            $chapter->unique_word_ids = json_encode([]);
            // processed_text is needed by getChapterForReader() -> getProcessedText()
            $chapter->setProcessedText([]);
            $chapter->save();

            $chapters[] = $chapter;
        }

        // Get reader data for chapter 1
        $response = $this->actingAs($user)->postJson('/chapters/get/reader', [
            'chapterId' => $chapters[0]->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('nextChapter', $chapters[1]->id);

        // Get reader data for last chapter (chapter 3)
        $response = $this->actingAs($user)->postJson('/chapters/get/reader', [
            'chapterId' => $chapters[2]->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('nextChapter', -1);
    }
}
