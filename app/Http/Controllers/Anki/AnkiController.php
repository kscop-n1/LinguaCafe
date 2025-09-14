<?php

namespace App\Http\Controllers\Anki;

use App\Http\Requests\Anki\AddCardToAnkiRequest;
use App\Services\AnkiApiService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AnkiController extends Controller
{
    public function __construct(
        private AnkiApiService $ankiApiService
    ) {
        //
    }

    public function addCardToAnki(AddCardToAnkiRequest $request)
    {
        $language = Auth::user()->selected_language;
        $word = mb_strtolower($request->post('word'));
        $reading = $request->post('reading') ?? '';
        $translation = $request->post('translation') ?? '';
        $exampleSentence = $request->post('exampleSentence') ?? '';

        $testResult = $this->ankiApiService->addWord($language, $word, $reading, $translation, $exampleSentence);

        return response()->json([
            'data' => $testResult,
        ], 200);
    }
}
