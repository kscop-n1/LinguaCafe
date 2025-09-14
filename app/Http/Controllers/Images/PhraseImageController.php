<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Http\Requests\Images\VocabularyImages\UpdatePhraseImageFromUrlRequest;
use App\Http\Requests\Images\VocabularyImages\UpdatePhraseImageRequest;
use App\Models\Phrase;
use App\Services\Images\VocabularyImageService;
use Illuminate\Support\Facades\Auth;

class PhraseImageController extends Controller
{
    public function __construct(
        public VocabularyImageService $vocabularyImageService,
    ) {
        //
    }

    public function show(Phrase $phrase)
    {
        $user = Auth::user();
        $imagePath = $this->vocabularyImageService->getImagePath($user, $phrase);

        return response()->file($imagePath);
    }

    public function update(UpdatePhraseImageRequest $request, Phrase $phrase)
    {
        $imageFile = $request->file('imageFile');
        $user = Auth::user();

        $fileName = $this->vocabularyImageService->uploadImage($user, $phrase, $imageFile);

        return response()->json([
            'data' => [
                'image' => $fileName,
            ],
        ]);
    }

    public function updateFromUrl(UpdatePhraseImageFromUrlRequest $request, Phrase $phrase)
    {
        $url = $request->validated('url');
        $user = Auth::user();

        $fileName = $this->vocabularyImageService->setImageFromUrl($phrase, $user, $url);

        return response()->json([
            'data' => [
                'image' => $fileName,
            ],
        ]);
    }

    public function destroy(Phrase $phrase)
    {
        $user = Auth::user();

        $this->vocabularyImageService->deleteImage($user, $phrase);

        return response()->json();
    }
}
