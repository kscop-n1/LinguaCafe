<?php

namespace App\Http\Controllers\Vocabulary;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vocabulary\SearchKanjiRequest;
use App\Http\Requests\Vocabulary\ShowKanjiRequest;
use App\Services\Vocabulary\VocabularyKanjiService;
use Illuminate\Support\Facades\Auth;

class VocabularyKanjiController extends Controller
{
    public function __construct(private VocabularyKanjiService $vocabularyKanjiService)
    {
        //
    }

    public function index(SearchKanjiRequest $request)
    {
        $user = Auth::user();

        $kanji = $this->vocabularyKanjiService->searchKanji(
            user: $user,
            language: LanguageConfig::load($user->selected_language),
            groupBy: $request->validated('kanjiGroupBy'),
            showUnknown: $request->validated('showUnknown')
        );

        return response()->json($kanji, 200);
    }

    public function show(ShowKanjiRequest $request)
    {

        $kanjiData = $this->vocabularyKanjiService->getkanjiDetails(
            user: Auth::user(),
            kanjiCharacter: $request->validated('kanji')
        );

        return response()->json($kanjiData, 200);
    }
}
