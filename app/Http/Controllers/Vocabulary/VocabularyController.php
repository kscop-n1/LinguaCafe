<?php

namespace App\Http\Controllers\Vocabulary;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vocabulary\SearchVocabularyRequest;
use App\Services\Vocabulary\ExampleSentenceService;
use App\Services\Vocabulary\VocabularyService;
use Illuminate\Support\Facades\Auth;

class VocabularyController extends Controller
{
    public function __construct(
        private VocabularyService $vocabularyService,
        private ExampleSentenceService $exampleSentenceService,
    ) {
        //
    }

    public function index(SearchVocabularyRequest $request)
    {
        $user = Auth::user();

        $searchResults = $this->vocabularyService->searchVocabulary(
            user: $user,
            language: LanguageConfig::load($user->selected_language),
            text: $request->validated('text'),
            bookId: $request->validated('book'),
            chapterId: $request->validated('chapter'),
            stage: $request->validated('stage'),
            phrases: $request->validated('phrases'),
            orderBy: $request->validated('orderBy'),
            translation: $request->validated('translation'),
            page: $request->validated('page')
        );

        return response()->json([
            'data' => $searchResults,
        ]);
    }
}
