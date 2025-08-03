<?php

namespace App\Services;

use App\Helpers\Language\LanguageConfig;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LanguageService
{
    // stores the python service container's name
    private $pythonService;

    public function __construct()
    {
        $this->pythonService = env('PYTHON_CONTAINER_NAME', 'linguacafe-python-service');
    }

    public function selectLanguage(User $user, LanguageConfig $language): void
    {
        $installedLanguages = $this->getInstalledLanguages();

        if ($language->requiresInstall() && !$installedLanguages->contains($language->name)) {
            throw new \Exception('This language is not installed.');
        }

        $user->selected_language = $language->name;
        $user->save();
    }

    public function getLanguageSelectionDialogData(Collection $supportedSourceLanguages, Collection $installableLanguages): array
    {
        $installedLanguages = $this->getInstalledLanguages();

        $languages = $supportedSourceLanguages->filter(function (string $supportedLanguage) use ($installableLanguages, $installedLanguages) {
            return !$installableLanguages->contains($supportedLanguage) || $installedLanguages->contains($supportedLanguage);
        });

        return [
            'languages' => $languages,
            'notInstalledLanguages' => $supportedSourceLanguages->count() - $languages->count(),
        ];
    }

    public function getInstalledLanguages(): Collection
    {
        $installedPackages = Cache::get('installed_languages');
        if (!$installedPackages) {
            Log::info('Installed python packages cache is empty.');
            $installedPackages = Http::get($this->pythonService . ':8678/packages/list');
            $installedPackages = json_decode($installedPackages);
            Cache::put('installed_languages', $installedPackages);
            Log::info('Installed python packages retrieved from the python server and has been cached.', [
                'installed_packages' => $installedPackages,
            ]);
        } else {
            Log::info('Installed python packages retrieved from cache.', [
                'installed_packages' => $installedPackages,
            ]);
        }

        $installedLanguages = array_merge($installedPackages->spacy_models, $installedPackages->stanza_models);

        return collect($installedLanguages);
    }

    public function installLanguage(LanguageConfig $language): void
    {
        if (!$language->requiresInstall()) {
            throw new \Exception('This language doesn\'t require install.');
        }

        $installResult = Http::timeout(60 * 20)
            ->post($this->pythonService . ':8678/packages/languages/install', [
                'language' => $language->name,
                'tokenizer' => $language->tokenizer,
            ]);

        $this->reloadLanguageCache();

        if ($installResult->getStatusCode() !== 200) {
            throw new \Exception('An error has occurred while installing the language.');
        }

        // Download KanjiVG
        if ($language->name == 'japanese') {
            $filePath = Storage::path('temp/kanjivg.zip');
            $extractPath = Storage::path('temp/kanjivg');
            File::delete($filePath);
            Storage::deleteDirectory('temp/kanjivg');
            Storage::deleteDirectory('images/kanjivg');

            $file = file_get_contents('https://github.com/KanjiVG/kanjivg/archive/master.zip');
            file_put_contents($filePath, $file);

            $zip = new \ZipArchive;
            $zipFile = $zip->open($filePath);
            if ($zipFile === true) {
                $zip->extractTo($extractPath);
                $zip->close();

                Storage::move('temp/kanjivg/kanjivg-master/kanji', 'images/kanjivg');
                Storage::deleteDirectory('temp/kanjivg');
                File::delete($filePath);
            } else {
                throw new \Exception('KanjiVG zip file could not be extracted.');
            }
        }
    }

    public function deleteInstalledLanguages(User $user, Collection $installableLanguages): void
    {
        if ($installableLanguages->contains($user->selected_language)) {
            $user->selected_language = 'spanish';
            $user->save();
        }

        Storage::deleteDirectory('images/kanjivg');

        $uninstallResult = Http::delete($this->pythonService . ':8678/packages/uninstall-all');
        if ($uninstallResult->getStatusCode() !== 200 && $uninstallResult->getStatusCode() !== 202) {
            throw new \Exception('An error has occurred while uninstalling languages.');
        }

        $this->reloadLanguageCache();
    }

    public function reloadLanguageCache(): void
    {
        $installedPackages = Http::get($this->pythonService . ':8678/packages/list');
        $installedPackages = json_decode($installedPackages);

        Cache::put('installed_languages', $installedPackages);

        Log::info('Installed python packages has been re-cached.', [
            'installed_packages' => $installedPackages,
        ]);
    }
}
