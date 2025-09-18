<?php

namespace App\Http\Controllers\Goals;

use App\Enums\GoalTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Goals\UpdateCalendarDataRequest;
use App\Models\GoalAchievement;
use App\Services\GoalService;
use Illuminate\Support\Facades\Auth;

class GoalAchievementController extends Controller
{
    public function __construct(
        private GoalService $goalService
    ) {
        //
    }

    public function updateOrStore(UpdateCalendarDataRequest $request, ?GoalAchievement $goalAchievement = null)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $goalType = GoalTypeEnum::from($request->validated('goalType'));
        $day = $request->validated('day');
        $quantity = $request->validated('quantity');

        $this->goalService->updateOrCreateGoalAchievement($user, $language, $goalAchievement, $goalType, $day, $quantity);

        return response()->noContent();
    }

    public function incrementReview()
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $this->goalService->updateOrCreateTodaysGoalAchievement($user, $language, GoalTypeEnum::REVIEW, 1);

        return response()->noContent();
    }
}
