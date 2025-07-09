<?php

namespace App\Http\Controllers;

use Exception;

// request classes
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Support\Facades\Auth;

// services
use App\Http\Requests\Books\CreateBookRequest;
use App\Http\Requests\Books\UpdateBookRequest;
use App\Http\Resources\Book\BookResource;

class BookController extends Controller
{
    public function __construct(private BookService $bookService)
    {
        //
    }

    public function getBook(Book $book)
    {
        if ($book->user_id !== Auth::user()->id) {
            throw new Exception('Book not found or unauthorized.');
        }

        return new BookResource($book);
    }

    public function getBooks()
    {
        $user = Auth::user();

        $books = $this->bookService->getBooks($user);

        return response()->json($books, 200);
    }

    public function getBookWordCounts(Book $book) {
        $user = Auth::user();

        $wordCounts = $this->bookService->getBookWordCounts($user, $book);

        return response()->json($wordCounts, 200);
    }

    public function createBook(CreateBookRequest $request) {
        $user = Auth::user();
        $name = $request->validated('name');
        $bookCoverFile = $request->file('cover');
        
        $this->bookService->createBook($user, $name, $bookCoverFile);
        
        return response()->noContent();
    }

    public function updateBook(UpdateBookRequest $request, Book $book) {
        $user = Auth::user();
        $name = $request->validated('name');
        $bookCoverFile = $request->file('cover');
        
        $this->bookService->updateBook($user, $book, $name, $bookCoverFile);
        
        return response()->noContent();
    }

    public function deleteBook(Book $book) {
        $user = Auth::user();

        $this->bookService->deleteBook($user, $book);
        
        return response()->noContent();
    }
}