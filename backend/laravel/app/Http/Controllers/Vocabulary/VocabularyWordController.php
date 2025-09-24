<?php

namespace App\Http\Controllers\Vocabulary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vocabulary\UpdateWordRequest;
use App\Http\Resources\Vocabulary\EncounteredWordResource;
use App\Models\EncounteredWord;
use App\Services\Vocabulary\VocabularyWordService;
use Illuminate\Support\Facades\Auth;

class VocabularyWordController extends Controller
{
    public function __construct(private VocabularyWordService $vocabularyWordService)
    {
        //
    }

    public function show(EncounteredWord $word)
    {
        $user = Auth::user();

        if ($user->id !== $word->user_id) {
            throw new \Exception('User has no permission to access this word.');
        }

        return new EncounteredWordResource($word);
    }

    public function update(UpdateWordRequest $request, EncounteredWord $word)
    {
        $user = Auth::user();

        $wordData = collect($request->validated())->except('stage');

        $stage = $request->validated('stage');

        $this->vocabularyWordService->updateWord($user, $word, $wordData, $stage);

        return response()->noContent();
    }
}
