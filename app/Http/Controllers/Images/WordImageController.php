<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
use App\Http\Requests\Images\VocabularyImages\UpdateWordImageFromUrlRequest;
use App\Http\Requests\Images\VocabularyImages\UpdateWordImageRequest;
use App\Models\EncounteredWord;
use App\Services\Images\VocabularyImageService;
use Illuminate\Support\Facades\Auth;

class WordImageController extends Controller
{
    public function __construct(
        public VocabularyImageService $vocabularyImageService,
    ) {
        //
    }

    public function show(EncounteredWord $word)
    {
        $user = Auth::user();
        $imagePath = $this->vocabularyImageService->getImagePath($user, $word);

        return response()->file($imagePath);
    }

    public function update(UpdateWordImageRequest $request, EncounteredWord $word)
    {
        $imageFile = $request->file('imageFile');
        $user = Auth::user();

        $fileName = $this->vocabularyImageService->uploadImage($user, $word, $imageFile);

        return response()->json([
            'data' => [
                'image' => $fileName,
            ],
        ]);
    }

    public function updateFromUrl(UpdateWordImageFromUrlRequest $request, EncounteredWord $word)
    {
        $url = $request->validated('url');
        $user = Auth::user();

        $fileName = $this->vocabularyImageService->setImageFromUrl($word, $user, $url);

        return response()->json([
            'data' => [
                'image' => $fileName,
            ],
        ]);
    }

    public function destroy(EncounteredWord $word)
    {
        $user = Auth::user();

        $this->vocabularyImageService->deleteImage($user, $word);

        return response()->json();
    }
}
