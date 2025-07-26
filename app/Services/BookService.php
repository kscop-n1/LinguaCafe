<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Book;

use App\Models\User;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\Enums\ChapterProcessingStatusEnum;
use Exception;
use stdClass;

class BookService {
    
    public function __construct() {
    }
    
    public function getBooks(User $user): Collection
    {
        $books = Book::query()
            ->where('user_id', $user->id)
            ->where('language', $user->selected_language)
            ->orderBy('updated_at', 'DESC')
            ->get();

        $books->transform(function(Book $book) {
            $book->wordCount = null;
            return $book;
        });

        return $books;
    }


    public function getBookWordCounts(User $user, Book $book): stdClass
    {
        if ($book->user_id !== $user->id) {
            throw new Exception('Book not found or unauthorized.');
        }

        $book = Book::query()
            ->where('user_id', $user->id)
            ->where('id', $book->id)
            ->firstOrFail();
        
        $words = EncounteredWord::query()
            ->select(['id', 'word', 'stage'])
            ->where('user_id', $user->id)
            ->where('language', $book->language)
            ->get()
            ->keyBy('id')
            ->toArray();

        return $book->getWordCounts($user, $words);
    }

    public function updateBookWordCount($userId, $bookId) {
        $bookWordCount = Chapter::query()
            ->where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value)
            ->sum('word_count');

        $bookWordCount = intval($bookWordCount);

        // update book word count
        Book::query()
            ->where('user_id', $userId)
            ->where('id', $bookId)
            ->update(['word_count' => $bookWordCount]);
    }

    public function createBook(User $user, string $name, ?UploadedFile $bookCoverFile): void
    {
        $book = new Book();
        $book->user_id = $user->id;
        $book->cover_image = null;
        $book->language = $user->selected_language;
        $book->name = $name;
        $book->save();
        
        if ($bookCoverFile) {
            $this->saveBookImage($book, $bookCoverFile);
        }
    }

    public function updateBook(User $user, Book $book, string $name, ?UploadedFile $bookCoverFile): void 
    {
        if ($book->user_id !== $user->id) {
            throw new Exception('Book not found or unauthorized.');
        }

        $book->name = $name;
        $book->save();
        
        if ($bookCoverFile) {
            $this->saveBookImage($book, $bookCoverFile);
        }
    }

    private function saveBookImage(Book $book, UploadedFile $bookCoverFile) {
        // TODO: make book cover_image nullable, and remove old empty strings values
        if ($book->cover_image !== '' && $book->cover_image !== null) {
            Storage::delete('/images/book_images/' . $book->cover_image);
        }

        // save image on server
        $timestamp = implode('_', explode(' ', Carbon::now()->toDateTimeString()));
        $fileName = $book->id . '_' . $timestamp . '.' . ($bookCoverFile->getClientOriginalExtension());
        $bookCoverFile->storeAs('/images/book_images/', $fileName);

        // save image in database
        $book->cover_image = $fileName;
        $book->save();
    }

    public function deleteBook(User $user, Book $book): void
    {
        if ($book->user_id !== $user->id) {
            throw new Exception('Book not found or unauthorized.');
        }
        
        Chapter::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->delete();
            
        if ($book->cover_image !== '' && $book->cover_image !== null) {
            Storage::delete('/images/book_images/' . $book->cover_image);
        }

        $book->bookmarks()->delete();
        $book->delete();
    }
}