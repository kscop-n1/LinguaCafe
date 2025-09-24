<?php

namespace App\Http\Controllers\Images;

use App\Http\Controllers\Controller;
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

    public function showBook(Book $book)
    {
        $user = Auth::user();

        $imagePath = $this->imageService->getBookImagePath($book, $user);

        return response()->file($imagePath);
    }

    public function showKanji(string $fileName)
    {
        $imagePath = Storage::path('/images/kanjivg/' . $fileName);

        return response()->file($imagePath);
    }
}
