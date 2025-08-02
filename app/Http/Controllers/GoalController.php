<?php

namespace App\Http\Controllers;

use App\Enums\GoalTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Goals\UpdateCalendarDataRequest;
use App\Http\Requests\Goals\UpdateGoalRequest;
use App\Http\Resources\Goal\GoalResourceCollection;
use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Services\GoalService;
use Illuminate\Support\Facades\Auth;

// TODO: separate Goal and GoalAchievement related code into 2 controllers
class GoalController extends Controller
{
    public function __construct(
        private GoalService $goalService
    ) {
        //
    }

    public function getGoals()
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $goals = $this->goalService->getGoals($user, $language);

        return new GoalResourceCollection($goals);
    }

    public function updateGoal(UpdateGoalRequest $request, Goal $goal)
    {
        $user = Auth::user();
        $newGoalQuantity = $request->post('newGoalQuantity');

        $this->goalService->updateGoal($user, $goal, $newGoalQuantity);

        return response()->noContent();
    }

    public function getCalendarData()
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $calendarData = $this->goalService->getCalendarData($user, $language);

        return response()->json([
            'data' => $calendarData,
        ]);
    }

    public function updateOrCreateGoalAchievement(UpdateCalendarDataRequest $request, ?GoalAchievement $goalAchievement = null)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $goalType = GoalTypeEnum::from($request->validated('goalType'));
        $day = $request->validated('day');
        $quantity = $request->validated('quantity');

        $this->goalService->updateOrCreateGoalAchievement($user, $language, $goalAchievement, $goalType, $day, $quantity);

        return response()->noContent();
    }

    public function updateReviewGoalAchievement()
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $this->goalService->updateOrCreateTodaysGoalAchievement($user, $language, GoalTypeEnum::REVIEW, 1);

        return response()->noContent();
    }
}
