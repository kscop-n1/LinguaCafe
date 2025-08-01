<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookmarkResourceCollection;
use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    private BookmarkService $bookmarkService;

    public function __construct()
    {
        $this->bookmarkService = new BookmarkService;
    }

    public function getNextChapterBookmarks()
    {
        $user = Auth::user();

        $bookmarks = $this->bookmarkService->getNextChapterBookmarks($user);

        return new BookmarkResourceCollection($bookmarks);
    }

    public function deleteBookmark(Bookmark $bookmark)
    {
        $user = Auth::user();

        $this->bookmarkService->deleteBookmark($user, $bookmark);

        return response()->noContent();
    }
}
