<?php

namespace App\Http\Controllers;

use App\Enums\GoalTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\Review\GetReviewItemsRequest;
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

    public function getReviewItems(GetReviewItemsRequest $request, ?Book $book = null, ?Chapter $chapter = null)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $practiceMode = $request->validated('practiceMode');

        $reviews = $this->reviewService->getReviewItems($user, $language, $book, $chapter, $practiceMode);

        $reviewData = new \stdClass;
        $reviewData->reviews = $reviews;
        $reviewData->language = $language->name;
        $reviewData->languageSpaces = $language->hasSpaces();

        return response()->json($reviewData, 200);
    }

    public function updateReadWordsGoal(UpdateReviewGoalRequest $request)
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
