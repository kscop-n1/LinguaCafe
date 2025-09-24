<?php

namespace App\Http\Controllers\Vocabulary;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vocabulary\StoreOrUpdateExampleSentenceRequest;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Services\Vocabulary\ExampleSentenceService;
use Illuminate\Support\Facades\Auth;

class ExampleSentenceController extends Controller
{
    public function __construct(
        private ExampleSentenceService $exampleSentenceService,
    ) {
        //
    }

    public function showForWord(EncounteredWord $word)
    {
        $user = Auth::user();

        $exampleSentence = $this->exampleSentenceService->getExampleSentence($user, $word);

        return response()->json([
            'data' => $exampleSentence,
        ]);
    }

    public function showForPhrase(Phrase $phrase)
    {
        $user = Auth::user();

        $exampleSentence = $this->exampleSentenceService->getExampleSentence($user, $phrase);

        return response()->json([
            'data' => $exampleSentence,
        ]);
    }

    public function createOrUpdate(StoreOrUpdateExampleSentenceRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $targetType = $request->validated('targetType');
        $targetId = $request->validated('targetId');
        $exampleSentenceWords = json_decode($request->validated('exampleSentenceWords'));

        $this->exampleSentenceService->createOrUpdateExampleSentence(
            $user,
            $language,
            $targetType,
            $targetId,
            $exampleSentenceWords
        );

        return response()->noContent();
    }
}
