<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Language\LanguageConfig;

class TextImportTest extends TestCase
{

    public function test_plain_text_import(): void
    {
        //TODO: this should only be installed languages
        $languages = LanguageConfig::all()->where('linguacafeSupport', true);
        $user = User::factory()->create();


        $languages->each(function(LanguageConfig $language, $index) use($user) {
            $this->print('Importing ' . $language->name . ' text.');

            $this->actingAs($user)->get('/languages/select/' . $language->name);
            $user->refresh();

            $fileName = Str::replace(' ', '_', $language->name) . '.txt';
            $text = Storage::disk('test')->get('texts/' . $fileName);
        
            $response = $this->actingAs($user)->post('/import', [
                'importType' => 'plain-text',
                'eBookChapterSortMethod' => 'default',
                'textProcessingMethod' => 'simple',
                'bookId' => -1,
                'bookName' => 'automatic_testing_text',
                'chapterName' => 'chapter',
                'maximumCharactersPerChapter' => 2000,
                'importText' => $text,
            ]);

            $response->assertStatus(200);
        });
        
    }

    public function test_subtitle_import(): void
    {

            //TEMP
            $this->assertFalse(false);
            //TEMP
            
            // $file = new UploadedFile(
            //     path: $path,
            //     originalName: $language->name . '.txt',
            //     test: true
            // );
    }

    private function print(string $message): void
    {
        fwrite(STDOUT, "{$message}\n");
    }
}
