<?php

namespace App\Http\Controllers;

use App\Helpers\Language\LanguageConfig;
use App\Services\GoalService;
use App\Services\SettingsService;

class HomeController extends Controller
{
    public function __construct(
        private GoalService $goalService,
        private SettingsService $settingsService
    ) {
        //
    }

    public function index()
    {
        return view('home');
    }

    public function getLanguageConfig()
    {
        $config = LanguageConfig::all();

        return response()->json($config);
    }
}
