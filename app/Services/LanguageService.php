<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Language\LanguageConfig;

class LanguageService {
    // stores the python service container's name
    private $pythonService;

    function __construct() {
        $this->pythonService = env('PYTHON_CONTAINER_NAME', 'linguacafe-python-service');
    }

    public function selectLanguage(User $user, LanguageConfig $language) {
        $installedLanguages = $this->getInstalledLanguages();
        /*
            This is an extra protection, to avoid switching to not installed
            languages. Since this should never happen in the software, it does not
            throw an exception.
        */
        if ($language->requiresInstall() && !in_array($language->name, $installedLanguages, true)) {
            return false;
        }

        $user->selected_language = $language->name;
        $user->save();
        
        return true;
    }

    public function getLanguageSelectionDialogData($supportedSourceLanguages, $installableLanguages) {
        $installedLanguages = $this->getInstalledLanguages();
        
        // select installed languages only
        $languages = [];
        $notInstalledLanguages = 0;
        foreach ($supportedSourceLanguages as $supportedLanguage) {
            // if it is a language that must be installed, and it is not installed currently
            if (in_array($supportedLanguage, $installableLanguages, true)
                && !in_array($supportedLanguage, $installedLanguages)) {
                $notInstalledLanguages ++;
                continue;
            }

            $languages[] = $supportedLanguage;
        }

        $responseData = new \stdClass();
        $responseData->languages = $languages;
        $responseData->notInstalledLanguages = $notInstalledLanguages;

        return $responseData;
    }
    
    public function getInstalledLanguages() {
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

        return $installedLanguages;
    }

    public function installLanguage(LanguageConfig $language) {
        if (!$language->requiresInstall()) {
            throw new \Exception('This language does not require install.');
        }

        $installResult = Http::timeout(60*20)
            ->post($this->pythonService . ':8678/packages/languages/install', [
                'language' => $language->name,
                'tokenizer' => $language->tokenizer,
            ]);

        $this->reloadLanguageCache();

        // Download KanjiVG
        if ($language->name == 'japanese') {
            $filePath = Storage::path('temp/kanjivg.zip');
            $extractPath = Storage::path('temp/kanjivg');
            File::delete($filePath);
            Storage::deleteDirectory('temp/kanjivg');
            Storage::deleteDirectory('images/kanjivg');

            $file = file_get_contents("https://github.com/KanjiVG/kanjivg/archive/master.zip");
            file_put_contents($filePath, $file);

            $zip = new \ZipArchive();
            $zipFile = $zip->open($filePath);
            if ($zipFile === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();

                Storage::move('temp/kanjivg/kanjivg-master/kanji', 'images/kanjivg');
                Storage::deleteDirectory('temp/kanjivg');
                File::delete($filePath);
            } else {
                throw new \Exception('KanjiVG zip file could not be extracted.');
            }
        }

        return $installResult;
    }

    public function deleteInstalledLanguages($user, $installableLanguages) {
        /*
            Reset selected language to the default spanish, 
            so the user won't have a language selected that has been uninstalled.
        */
        if (in_array($user->selected_language, $installableLanguages)) {
            $user->selected_language = 'spanish';
            $user->save();
        }

        // delete KanjiVG files
        Storage::deleteDirectory('images/kanjivg');

        // delete python language models
        $uninstallResult = Http::delete($this->pythonService . ':8678/packages/uninstall-all');

        $this->reloadLanguageCache();

        return $uninstallResult;
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