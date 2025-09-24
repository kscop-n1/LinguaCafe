<?php

namespace App\Services;

use App\Enums\BookmarkTypeEnum;
use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;

class BookmarkService
{
    public function getBookmarks(User $user, ?BookmarkTypeEnum $type): Collection
    {
        $bookmarks = Bookmark::query()
            ->where('user_id', '=', $user->id)
            ->where('language', '=', $user->selected_language)
            ->when($type !== null, function ($query) use ($type) {
                $query->where('type', '=', $type->value);
            })
            ->with([
                'chapter:id,name',
                'book:id,name,cover_image',
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        return $bookmarks;
    }

    public function setNextChapterBookmark(User $user, Chapter $currentChapter): void
    {
        if ($currentChapter->user_id !== $user->id) {
            throw new Exception('Chapter not found or unauthorized.');
        }

        $nextChapter = Chapter::query()
            ->where('book_id', '=', $currentChapter->book_id)
            ->where('id', '>', $currentChapter->id)
            ->orderBy('id', 'asc')
            ->first();

        $bookmark = $currentChapter->book
            ->bookmarks()
            ->where('type', BookmarkTypeEnum::NEXT_CHAPTER->value)
            ->first();

        if (!$nextChapter) {
            if ($bookmark) {
                $bookmark->delete();
            }

            return;
        }

        if (!$bookmark) {
            $bookmark = new Bookmark();
            $bookmark->user_id = $user->id;
            $bookmark->language = $currentChapter->language;
            $bookmark->book_id = $currentChapter->book_id;
            $bookmark->type = BookmarkTypeEnum::NEXT_CHAPTER->value;
        }

        $bookmark->chapter_id = $nextChapter->id;
        $bookmark->touch();
        $bookmark->save();
    }

    public function deleteBookmark(User $user, Bookmark $bookmark): void
    {
        if ($bookmark->user_id !== $user->id) {
            throw new Exception('Bookmark not found or unauthorized.');
        }

        $bookmark->delete();
    }
}
