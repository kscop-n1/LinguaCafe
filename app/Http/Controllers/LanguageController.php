<?php

namespace App\Http\Controllers;

use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Languages\ChangeLanguageRequest;
use App\Http\Requests\Languages\InstallLanguageRequest;
use App\Services\GoalService;
use App\Services\LanguageService;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    public function __construct(
        private LanguageService $languageService,
        private GoalService $goalService
    ) {
        //
    }

    public function selectLanguage($language, ChangeLanguageRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($language);

        $this->languageService->selectLanguage($user, $language);
        $this->goalService->createGoalsForLanguage($user, $language);

        return response()->noContent();
    }

    public function getLanguageSelectionDialogData()
    {
        $supportedSourceLanguages = LanguageConfig::all()->where('linguacafeSupport', '=', true)->pluck('name');
        $installableLanguages = LanguageConfig::all()->where('installRequired', '=', true)->pluck('name');

        $languageData = $this->languageService->getLanguageSelectionDialogData($supportedSourceLanguages, $installableLanguages);

        return response()->json([
            'data' => $languageData,
        ]);
    }

    public function getAdminLanguageSettingsData()
    {
        $installableLanguages = LanguageConfig::all()->where('installRequired', '=', true)->pluck('name')->toArray();
        $installedLanguages = $this->languageService->getInstalledLanguages();

        return response()->json([
            'data' => [
                'languages' => $installableLanguages,
                'installedLanguages' => $installedLanguages,
            ],
        ]);
    }

    public function installLanguage(InstallLanguageRequest $request)
    {
        $language = LanguageConfig::load($request->validated('language'));

        $installResult = $this->languageService->installLanguage($language);

        return response()->noContent();
    }

    public function deleteInstalledLanguages()
    {
        $installableLanguages = LanguageConfig::all()->where('installRequired', '=', true)->pluck('name');
        $user = Auth::user();

        $this->languageService->deleteInstalledLanguages($user, $installableLanguages);

        return response()->noContent();
    }
}
