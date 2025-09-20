<?php

namespace App\Http\Controllers\Statistics;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService,
    ) {
        //
    }

    public function show()
    {
        $user = Auth::user();
        $language = LanguageConfig::load($user->selected_language);

        $statistics = $this->statisticsService->getStatistics($user, $language);

        return response()->json($statistics);
    }
}
