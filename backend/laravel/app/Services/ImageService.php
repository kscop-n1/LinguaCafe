<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function __construct() {}

    public function getBookImagePath(Book $book, User $user): string
    {
        if ($book->user_id !== $user->id) {
            throw new \Exception('The file does not exist, or it belongs to a different user.');
        }

        if (is_null($book->cover_image)) {
            return Storage::disk('default-files')->path('/images/book_images/default.svg');
        } else {
            return Storage::path('/images/book_images/' . $book->cover_image);
        }
    }
}
