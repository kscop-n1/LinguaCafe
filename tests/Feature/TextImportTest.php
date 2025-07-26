<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use App\Services\LanguageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Language\LanguageConfig;

class TextImportTest extends TestCase
{
    
    public function test_plain_text_import(): void
    {
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed', ['--force']);
        
        $user = User::factory()->create();
        $installedLanguages = (new LanguageService())->getInstalledLanguages();
        $languages = $this->getInstalledLanguages();

        $languages->each(function(LanguageConfig $language) use($user) {
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
        $user = User::first();
        $languages = $this->getInstalledLanguages();

        $languages->each(function(LanguageConfig $language) use($user) {
            $this->print('Importing ' . $language->name . ' subtitle.');

            $this->actingAs($user)->get('/languages/select/' . $language->name);
            $user->refresh();

            $fileName = Str::replace(' ', '_', $language->name) . '.srt';
            $fileContents =  Storage::disk('test')->get('subtitles/' . $fileName);

            $file = new UploadedFile(
                path: Storage::disk('test')->path('subtitles/' . $fileName),
                originalName: $language->name . '.srt',
                test: true,
            );

            $fileContentResponse = $this->actingAs($user)->post('/subtitle/get-subtitle-file-content', [
                'subtitleFile' => $file,
            ]);

            $fileContentResponse->assertStatus(200);

            Storage::disk('test')->put('subtitles/' . $fileName, $fileContents);          

            $response = $this->actingAs($user)->post('/import', [
                'importType' => 'subtitle-file',
                'eBookChapterSortMethod' => 'default',
                'textProcessingMethod' => 'simple',
                'bookId' => -1,
                'bookName' => 'automatic_testing_subtitle',
                'chapterName' => 'chapter',
                'maximumCharactersPerChapter' => 2000,
                'importSubtitles' => $fileContentResponse->getContent(),
            ]);
            
            $response->assertStatus(200);
        });
        
        $this->print('Tests finished, test user: ' . $user->email);
    }

    private function print(string $message): void
    {
        fwrite(STDOUT, "{$message}\n");
    }

    private function getInstalledLanguages(): Collection
    {
        $installedLanguages = (new LanguageService())->getInstalledLanguages();
        $installedLanguages = collect($installedLanguages);
        return LanguageConfig::all()->filter(function(LanguageConfig $language) use($installedLanguages) {
            if (!$language->hasLinguaCafeSupport()) {
                return false;
            }
            
            return $installedLanguages->contains($language->name) || !$language->requiresInstall();
        });
    }
}
