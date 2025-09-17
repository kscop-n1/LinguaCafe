<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Books\StoreBookRequest;
use App\Http\Requests\Books\UpdateBookRequest;
use App\Http\Resources\Book\BookResource;
use App\Models\Book;
use App\Services\BookService;
use App\Services\ChapterService;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function __construct(
        private BookService $bookService,
        private ChapterService $chapterService,
    ) {
        //
    }

    public function index()
    {
        $user = Auth::user();

        $books = $this->bookService->getBooks($user);

        return response()->json($books, 200);
    }

    public function show(Book $book)
    {
        if ($book->user_id !== Auth::user()->id) {
            throw new \Exception('Book unauthorized.');
        }

        return new BookResource($book);
    }

    public function store(StoreBookRequest $request)
    {
        $user = Auth::user();
        $name = $request->validated('name');
        $bookCoverFile = $request->file('cover');

        $this->bookService->createBook($user, $name, $bookCoverFile);

        return response()->noContent();
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $user = Auth::user();
        $name = $request->validated('name');
        $bookCoverFile = $request->file('cover');

        $this->bookService->updateBook($user, $book, $name, $bookCoverFile);

        return response()->noContent();
    }

    public function destroy(Book $book)
    {
        $user = Auth::user();

        $this->bookService->deleteBook($user, $book);

        return response()->noContent();
    }

    public function wordCounts(Book $book)
    {
        $user = Auth::user();

        $wordCounts = $this->bookService->getWordCounts($user, $book);

        return response()->json($wordCounts, 200);
    }

    public function retryFailedChapters(Book $book)
    {
        $user = Auth::user();

        $this->chapterService->retryFailedChapters($user, $book);

        return response()->noContent();
    }
}
