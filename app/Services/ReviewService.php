<?php

namespace App\Services;

use App\DataTransferObjects\Review\ReviewWordAndPhraseIdData;
use App\Helpers\Language\LanguageConfig;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Models\User;
use Illuminate\Support\Collection;

class ReviewService
{
    public function __construct() {}

    public function getReviewItems(User $user, LanguageConfig $language, ?Book $book, ?Chapter $chapter, bool $practiceMode): Collection
    {

        $this->validateBook($user, $book);
        $this->validateChapter($user, $chapter);

        $wordAndPhraseIds = $this->getWordAndPhraseIds($book, $chapter);
        $reviewWords = EncounteredWord::query()
            ->selectRaw('*, \'word\' as type')
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('stage', '<', 0)
            ->when(!$practiceMode, function ($query) {
                $query->where(function ($query) {
                    $query->whereDate('next_review', '<=', today()->format('Y-m-d'));
                    $query->orWhere('relearning', true);
                });
            })
            ->when($wordAndPhraseIds, function ($query) use ($wordAndPhraseIds) {
                $query->whereIntegerInRaw('id', $wordAndPhraseIds->wordIds);
            })
            ->inRandomOrder()
            ->get();

        $reviewPhrases = Phrase::query()
            ->selectRaw('*, \'phrase\' as type')
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('stage', '<', 0)
            ->when(!$practiceMode, function ($query) {
                $query->where(function ($query) {
                    $query->whereDate('next_review', '<=', today()->format('Y-m-d'));
                    $query->orWhere('relearning', true);
                });
            })
            ->when($wordAndPhraseIds, function ($query) use ($wordAndPhraseIds) {
                $query->whereIntegerInRaw('id', $wordAndPhraseIds->phraseIds);
            })
            ->inRandomOrder()
            ->get();

        return $reviewWords->merge($reviewPhrases)->values();
    }

    private function validateBook(User $user, ?Book $book): void
    {
        if ($book && $book->user_id !== $user->id) {
            throw new \Exception('Book does not exist, or it belongs to a different user.');
        }
    }

    private function validateChapter(User $user, ?Chapter $chapter): void
    {
        if ($chapter && $chapter->user_id !== $user->id) {
            throw new \Exception('Book does not exist, or it belongs to a different user.');
        }
    }

    private function getChapterWordAndPhraseIds(Chapter $chapter): ReviewWordAndPhraseIdData
    {
        $wordIds = collect(json_decode($chapter->unique_word_ids));
        $phraseIds = collect();

        $words = collect($chapter->getProcessedText());
        $words->each(function ($word) use (&$phraseIds) {
            $phraseIds = $phraseIds->merge($word->phrase_ids);
        });

        return new ReviewWordAndPhraseIdData(
            wordIds: $wordIds,
            phraseIds: $phraseIds->unique()
        );
    }

    private function getBookWordAndPhraseIds(Book $book): ReviewWordAndPhraseIdData
    {
        $wordIds = collect();
        $phraseIds = collect();

        $book->chapters?->each(function (Chapter $chapter) use (&$wordIds, &$phraseIds) {
            $chapterData = $this->getChapterWordAndPhraseIds($chapter);

            $wordIds = $wordIds->merge($chapterData->wordIds);
            $phraseIds = $phraseIds->merge($chapterData->phraseIds);
        });

        return new ReviewWordAndPhraseIdData(
            wordIds: $wordIds->unique(),
            phraseIds: $phraseIds->unique(),
        );
    }

    private function getWordAndPhraseIds(?Book $book, ?Chapter $chapter): ?ReviewWordAndPhraseIdData
    {
        if ($chapter) {
            return $this->getChapterWordAndPhraseIds($chapter);
        }

        if ($book) {
            return $this->getBookWordAndPhraseIds($book);
        }

        return null;
    }
}
