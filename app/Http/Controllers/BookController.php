<?php

namespace App\Http\Controllers;

use App\Models\Book;

// request classes
use App\Services\BookService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Books\CreateBookRequest;

// services
use App\Http\Requests\Books\UpdateBookRequest;

class BookController extends Controller
{
    public function __construct(private BookService $bookService)
    {
        //
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