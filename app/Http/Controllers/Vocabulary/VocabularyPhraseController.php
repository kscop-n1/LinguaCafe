<?php

namespace App\Http\Controllers\Vocabulary;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vocabulary\StorePhraseRequest;
use App\Http\Requests\Vocabulary\UpdatePhraseRequest;
use App\Http\Resources\Vocabulary\PhraseResource;
use App\Models\Phrase;
use App\Services\Vocabulary\VocabularyPhraseService;
use Illuminate\Support\Facades\Auth;

class VocabularyPhraseController extends Controller
{
    public function __construct(private VocabularyPhraseService $vocabularyPhraseService)
    {
        //
    }

    public function show(Phrase $phrase)
    {
        $user = Auth::user();

        if ($user->id !== $phrase->user_id) {
            throw new \Exception('User has no permission to access this phrase.');
        }

        return new PhraseResource($phrase);
    }

    public function store(StorePhraseRequest $request)
    {
        $user = Auth::user();
        $language = LanguageConfig::load(Auth::user()->selected_language);
        $words = json_decode($request->words);
        $stage = $request->stage;
        $reading = is_null($request->reading) ? '' : $request->reading;
        $translation = is_null($request->translation) ? '' : $request->translation;

        // TODO: make phrase fields nullable
        $phraseId = $this->vocabularyPhraseService->createPhrase($user, $language, $words, $stage, $reading, $translation);

        return response()->json([
            'data' => $phraseId,
        ]);
    }

    public function update(UpdatePhraseRequest $request, Phrase $phrase)
    {
        $user = Auth::user();

        $phraseData = collect($request->validated())->except('stage');
        $stage = $request->validated('stage');

        $this->vocabularyPhraseService->updatePhrase($user, $phrase, $phraseData, $stage);

        return response()->noContent();
    }

    public function destroy(Phrase $phrase)
    {
        $user = Auth::user();

        $this->vocabularyPhraseService->deletePhrase($user, $phrase);

        return response()->noContent();
    }
}
