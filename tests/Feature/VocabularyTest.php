<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EncounteredWord;
use App\Models\Phrase;
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

        $word = EncounteredWord::create([
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'word' => 'testword',
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

        $word1 = EncounteredWord::create([
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'word' => 'apple'
        ]);

        $word2 = EncounteredWord::create([
            'user_id' => $user->id,
            'language' => 'japanese',
            'stage' => 2,
            'word' => 'banana'
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

        $phrase = Phrase::create([
            'user_id' => $user->id,
            'language' => 'english',
            'words' => json_encode(['how', 'are', 'you']),
            'translation' => 'old translation'
        ]);

        $response = $this->actingAs($user)->postJson('/vocabulary/phrases/update', [
            'id' => $phrase->id,
            'words' => 'how goes it',
            'translation' => 'new translation'
        ]);

        $response->assertStatus(200);

        $phrase->refresh();
        $this->assertEquals('how goes it', $phrase->words_searchable);
        $this->assertEquals(json_encode(['how', 'goes', 'it']), $phrase->words);
        $this->assertEquals('new translation', $phrase->translation);
    }

    public function test_phrase_update_duplicate_fails(): void
    {
        $user = User::factory()->create();
        $user->selected_language = 'english';
        $user->save();

        $phrase1 = Phrase::create([
            'user_id' => $user->id,
            'language' => 'english',
            'words' => json_encode(['hello', 'world']),
            'words_searchable' => 'hello world'
        ]);

        $phrase2 = Phrase::create([
            'user_id' => $user->id,
            'language' => 'english',
            'words' => json_encode(['hello', 'there']),
            'words_searchable' => 'hello there'
        ]);

        $response = $this->actingAs($user)->postJson('/vocabulary/phrases/update', [
            'id' => $phrase2->id,
            'words' => 'hello world'
        ]);

        $response->assertStatus(500);
        $phrase2->refresh();
        $this->assertEquals('hello there', $phrase2->words_searchable);
    }
}
