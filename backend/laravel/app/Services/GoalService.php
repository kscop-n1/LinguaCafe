<?php

namespace App\Services;

use App\Enums\GoalTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Models\EncounteredWord;
use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function __construct() {}

    public function createGoalsForLanguage(User $user, LanguageConfig $language): void
    {
        $goal = Goal::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->first();

        if (!$goal) {
            $goal = new Goal;
            $goal->user_id = $user->id;
            $goal->language = $language->name;
            $goal->name = 'Reviews';
            $goal->type = 'review';
            $goal->quantity = 0;
            $goal->save();

            $goal = new Goal;
            $goal->user_id = $user->id;
            $goal->language = $language->name;
            $goal->name = 'Reading';
            $goal->type = 'read_words';
            $goal->quantity = 1000;
            $goal->save();

            $goal = new Goal;
            $goal->user_id = $user->id;
            $goal->language = $language->name;
            $goal->name = 'New words';
            $goal->type = 'learn_words';
            $goal->quantity = 10;
            $goal->save();
        }
    }

    public function updateOrCreateTodaysGoalAchievement(User $user, LanguageConfig $language, GoalTypeEnum $goalType, int $achievedQuantity): void
    {
        $goal = Goal::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('type', $goalType->value)
            ->firstOrFail();

        $achievement = GoalAchievement::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('goal_id', $goal->id)
            ->where('day', Carbon::now()->toDateString())
            ->first();

        if (!$achievement) {
            $achievement = new GoalAchievement;
            $achievement->language = $language->name;
            $achievement->user_id = $user->id;
            $achievement->goal_id = $goal->id;
            $achievement->achieved_quantity = 0;
            $achievement->goal_quantity = $goal->quantity;
            $achievement->day = Carbon::now()->toDateString();
        }

        $achievement->achieved_quantity += $achievedQuantity;
        $achievement->save();
    }

    public function getGoals(User $user, LanguageConfig $language): Collection
    {
        $goals = Goal::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->get();

        $goals->transform(function (Goal $goal) {
            $goal->todaysQuantity = $goal->getTodaysQuantity();

            return $goal;
        });

        return $goals;
    }

    public function updateGoal(User $user, Goal $goal, int $newGoalQuantity): void
    {
        if ($goal->user_id !== $user->id) {
            throw new \Exception('Goal not found or unauthorized.');
        }

        $goal->quantity = $newGoalQuantity;
        $goal->save();

        // also update today's goal achievement
        $achievement = GoalAchievement::query()
            ->where('user_id', $user->id)
            ->where('goal_id', $goal->id)
            ->where('day', Carbon::today()->format('Y-m-d'))
            ->first();

        if ($achievement) {
            $achievement->goal_quantity = $newGoalQuantity;
            $achievement->save();
        }
    }

    // TODO: very old code, this method should be rewritten with relationships, probably for v1.0
    public function getCalendarData(User $user, LanguageConfig $language): array
    {
        $calendarData = [];

        // query goal achievements
        $goalAchievements = GoalAchievement::where('user_id', $user->id)->where('language', $language->name)->get();
        $goalAchievements = DB::table('goal_achievements')
            ->leftJoin('goals', 'goal_achievements.goal_id', '=', 'goals.id')
            ->select('goals.name', 'goals.type', 'goal_achievements.id', 'goal_achievements.day', 'goal_achievements.achieved_quantity', 'goal_achievements.goal_quantity')
            ->where('goals.user_id', $user->id)
            ->where('goals.language', $language->name)->get();

        // add goal achievements to calendar data
        foreach ($goalAchievements as $achievement) {
            // look for achievement date in calendar data
            $dayIndex = -1;
            foreach ($calendarData as $index => $day) {
                if ($day->day == $achievement->day) {
                    $dayIndex = $index;
                    break;
                }
            }

            // update or append calendar data
            $achievementData = new \stdClass;
            $achievementData->id = $achievement->id;
            $achievementData->name = $achievement->name;
            $achievementData->type = $achievement->type;
            $achievementData->day = $achievement->day;
            $achievementData->achievedQuantity = $achievement->achieved_quantity;
            $achievementData->goalQuantity = $achievement->goal_quantity;

            if ($dayIndex !== -1) {
                array_push($calendarData[$dayIndex]->achievements, $achievementData);
            } else {
                $dayData = new \stdClass;
                $dayData->day = $achievement->day;
                $dayData->achievements = [$achievementData];
                $dayData->reviewsDue = 0;
                array_push($calendarData, $dayData);
            }
        }

        // query the count of reviews for each day
        $reviewsDue = EncounteredWord::where('user_id', $user->id)
            ->where('language', $language->name)
            ->whereNotNull('next_review')
            ->selectRaw(DB::raw('next_review as day, count(id) as quantity'))
            ->groupBy('next_review')->get();

        // add reviews due to calendar data
        foreach ($reviewsDue as $review) {
            // look for review date in calendar data
            $dayIndex = -1;
            foreach ($calendarData as $index => $day) {
                if ($day->day == $review->day) {
                    $dayIndex = $index;
                    break;
                }
            }

            // update or append calendar data
            if ($dayIndex !== -1) {
                $calendarData[$dayIndex]->reviewsDue = $review->quantity;
            } else {
                $dayData = new \stdClass;
                $dayData->day = $review->day;
                $dayData->achievements = [];
                $dayData->reviewsDue = $review->quantity;
                array_push($calendarData, $dayData);
            }
        }

        return $calendarData;
    }

    public function updateOrCreateGoalAchievement(
        User $user,
        LanguageConfig $language,
        ?GoalAchievement $goalAchievement,
        GoalTypeEnum $goalType,
        string $day,
        int $quantity
    ): void {
        if ($goalAchievement) {
            $goalAchievement->achieved_quantity = $quantity;
            $goalAchievement->save();
        } else {
            $goal = Goal::query()
                ->where('user_id', $user->id)
                ->where('language', $language->name)
                ->where('type', $goalType->value)
                ->firstOrFail();

            $achievement = new GoalAchievement;
            $achievement->user_id = $user->id;
            $achievement->language = $language->name;
            $achievement->goal_id = $goal->id;
            $achievement->achieved_quantity = $quantity;
            // TODO: goal_quantity should be null for review
            $achievement->goal_quantity = $goal->type == 'review' ? 1 : $goal->quantity;
            $achievement->day = $day;
            $achievement->save();
        }
    }
}
