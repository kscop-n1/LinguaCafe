<?php

namespace App\Http\Controllers\Goals;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Goals\UpdateGoalRequest;
use App\Http\Resources\Goal\GoalResourceCollection;
use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Support\Facades\Auth;

class GoalController extends Controller
{
    public function __construct(
        private GoalService $goalService
    ) {
        //
    }

    public function index()
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $goals = $this->goalService->getGoals($user, $language);

        return new GoalResourceCollection($goals);
    }

    public function update(UpdateGoalRequest $request, Goal $goal)
    {
        $user = Auth::user();
        $newGoalQuantity = $request->validated('newGoalQuantity');

        $this->goalService->updateGoal($user, $goal, $newGoalQuantity);

        return response()->noContent();
    }

    public function calendar()
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $calendarData = $this->goalService->getCalendarData($user, $language);

        return response()->json([
            'data' => $calendarData,
        ]);
    }
}
