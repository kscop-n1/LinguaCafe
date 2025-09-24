<?php

namespace App\Http\Controllers\Reviews;

use App\Enums\GoalTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\UpdateReviewGoalRequest;
use App\Models\Book;
use App\Models\Chapter;
use App\Services\GoalService;
use App\Services\ReviewService;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
        private GoalService $goalService
    ) {
        //
    }

    public function show(bool $practiceMode, ?Book $book = null, ?Chapter $chapter = null)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);

        $reviews = $this->reviewService->getReviewItems($user, $language, $book, $chapter, $practiceMode);

        return response()->json([
            'reviews' => $reviews,
            'language' => $language->name,
            'languageSpaces' => $language->hasSpaces(),
        ]);
    }

    public function updateGoal(UpdateReviewGoalRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $readWords = $request->validated('readWords');

        $this->goalService->updateOrCreateTodaysGoalAchievement(
            user: $user,
            language: $language,
            goalType: GoalTypeEnum::READ_WORDS,
            achievedQuantity: $readWords
        );

        return response()->noContent();
    }
}
