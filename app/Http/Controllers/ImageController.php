<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\ImageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct(
        private ImageService $imageService
    ) {
        //
    }

    public function getBookImage(Book $book)
    {
        $user = Auth::user();

        $imagePath = $this->imageService->getBookImagePath($book, $user);

        return response()->file($imagePath);
    }

    public function getKanjiImage(string $fileName)
    {
        $imagePath = Storage::path('/images/kanjivg/' . $fileName);

        return response()->file($imagePath);
    }
}
