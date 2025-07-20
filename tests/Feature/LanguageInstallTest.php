<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Language\LanguageConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LanguageInstallTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_uninstall_languages(): void
    {
        return;
        $this->print("\n\n\n\n\n\n\n\n\n\n\n\n");
        $this->print('──────────────────────────────────────────────────────────');
        $this->print("Tests starting.\n");

        // auth
        $user = User::factory()->create();

        // delete packages directory
        $this->print('Uninstalling languages.');
        $response = $this->actingAs($user)->delete('/languages/installed/delete');
        $response->assertStatus(200);
        $this->print('Languages uninstalled.');
        $this->print('──────────────────────────────────────────────────────────');

        // assert folder exist
        $this->assertFalse(File::exists(Storage::path('/packages')));
    }

    /**
     * @depends test_uninstall_languages
     */
    public function test_install_languages(): void
    {
        return;
        $user = User::factory()->create();

        $this->print("Installing languages.\n");
        
        $languages = LanguageConfig::all()
            ->where('installRequired', '=', true)
            ->pluck('name');

        $this->print("Languages with install requirement: " . $languages->join(', ') . "\n");

        $languages->each(function ($language) use($user) {
            $this->print("Installing {$language}.");

            $response = $this->actingAs($user)->post('/languages/install', [
                'language' => $language,
            ]);

            $response->assertStatus(200);

            $this->print("{$language} installed successfully.\n");
        });


        $this->print("Every language has been installed successfully.\n");
        $this->print('──────────────────────────────────────────────────────────');
        
    }

    private function print(string $message): void
    {
        fwrite(STDOUT, "{$message}\n");
    }
}
