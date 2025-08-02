<?php

namespace App\Http\Controllers;

use App\Helpers\Language\LanguageConfig;
use App\Services\GoalService;
use App\Services\SettingsService;
use App\Services\StatisticsService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService,
        private GoalService $goalService,
        private SettingsService $settingsService
    ) {
        //
    }

    public function index()
    {
        return view('home');
    }

    public function getStatistics()
    {
        $user = Auth::user();
        $language = LanguageConfig::load($user->selected_language);

        $statistics = $this->statisticsService->getStatistics($user, $language);

        return response()->json($statistics);
    }

    public function getLanguageConfig()
    {
        $config = LanguageConfig::all();

        return response()->json($config);
    }
}
