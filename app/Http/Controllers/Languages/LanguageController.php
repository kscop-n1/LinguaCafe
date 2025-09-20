<?php

namespace App\Http\Controllers\Languages;

use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Languages\ChangeLanguageRequest;
use App\Http\Requests\Languages\InstallLanguageRequest;
use App\Services\GoalService;
use App\Services\LanguageService;
use Illuminate\Support\Facades\Auth;
use Laravel\Horizon\Http\Controllers\Controller;

class LanguageController extends Controller
{
    public function __construct(
        private LanguageService $languageService,
        private GoalService $goalService
    ) {
        //
    }

    public function index()
    {
        $config = LanguageConfig::all();

        return response()->json($config);
    }

    public function indexForDialog()
    {
        $supportedSourceLanguages = LanguageConfig::all()->where('linguacafeSupport', '=', true)->pluck('name');
        $installableLanguages = LanguageConfig::all()->where('installRequired', '=', true)->pluck('name');

        $languageData = $this->languageService->getLanguageSelectionDialogData($supportedSourceLanguages, $installableLanguages);

        return response()->json([
            'data' => $languageData,
        ]);
    }

    public function indexForAdmin()
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

    public function select($language, ChangeLanguageRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load($language);

        $this->languageService->selectLanguage($user, $language);
        $this->goalService->createGoalsForLanguage($user, $language);

        return response()->noContent();
    }

    public function install(InstallLanguageRequest $request)
    {
        $language = LanguageConfig::load($request->validated('language'));

        $this->languageService->installLanguage($language);

        return response()->noContent();
    }

    public function destroy()
    {
        $installableLanguages = LanguageConfig::all()->where('installRequired', '=', true)->pluck('name');
        $user = Auth::user();

        $this->languageService->deleteInstalledLanguages($user, $installableLanguages);

        return response()->noContent();
    }
}
