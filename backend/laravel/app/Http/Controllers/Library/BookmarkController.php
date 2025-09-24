<?php

namespace App\Http\Controllers\Library;

use App\Enums\BookmarkTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookmarkResourceCollection;
use App\Models\Bookmark;
use App\Services\BookmarkService;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function __construct(private BookmarkService $bookmarkService)
    {
        //
    }

    public function index(?BookmarkTypeEnum $type = null)
    {
        $user = Auth::user();

        $bookmarks = $this->bookmarkService->getBookmarks($user, $type);

        return new BookmarkResourceCollection($bookmarks);
    }

    public function destroy(Bookmark $bookmark)
    {
        $user = Auth::user();

        $this->bookmarkService->deleteBookmark($user, $bookmark);

        return response()->noContent();
    }
}
