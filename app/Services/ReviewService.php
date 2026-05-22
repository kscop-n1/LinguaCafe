<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Enums\ChapterProcessingStatusEnum;

class ReviewService {
    
    public function __construct() {
    }

    public function getReviewItems($userId, $language, $bookId, $chapterId, $practiceMode, $languagesWithoutSpaces) {
        // check if book exists
        if ($bookId !== -1) {
            $book = Book
                ::where('user_id', $userId)
                ->where('id', $bookId)
                ->where('language', $language)
                ->first();
            
            if (!$book) {
                throw new \Exception('Book does not exist, or it belongs to a different user.');
            }
        }

        // check if chapter exists
        if ($chapterId !== -1) {
            $chapter = Chapter
                ::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->where('id', $chapterId)
                ->where('language', $language)
                ->first();
            
            if (!$chapter) {
                throw new \Exception('Chapter does not exist, or it belongs to a different book or user.');
            }
        }

        // base query
        $reviewWords = EncounteredWord
            ::where('user_id', $userId)
            ->where('language', $language)
            ->where('stage', '<', '0');

        $reviewPhrases = Phrase
            ::where('user_id', $userId)
            ->where('language', $language)
            ->where('stage', '<', '0');

        // practice mode
        if (!$practiceMode) {
            $reviewWords = $reviewWords->where(function($query) {
                $query->whereDate('next_review', '<=', Carbon::today()->toDateString());
                $query->orWhere('relearning', true);
            });

            $reviewPhrases = $reviewPhrases->where(function($query) {
                $query->whereDate('next_review', '<=', Carbon::today()->toDateString());
                $query->orWhere('relearning', true);
            });
        }
        
        // retrieve chapter words and phrases by chapter id
        $uniqueWords = [];
        $uniquePhraseIds = [];
        if ($chapterId !== -1 || $bookId !== -1) {
            $chapters = Chapter
                ::select(['id', 'unique_words', 'unique_phrase_ids'])
                ->where('user_id', $userId)
                ->where('language', $language)
                ->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value);

            if ($chapterId !== -1) {
                $chapters = $chapters->where('id', $chapterId);
            } else {
                $chapters = $chapters->where('book_id', $bookId);
            }

            $chapters = $chapters->get();

            foreach ($chapters as $chapter) {
                foreach (json_decode($chapter->unique_words) ?: [] as $word) {
                    $uniqueWords[mb_strtolower($word, 'UTF-8')] = true;
                }

                foreach ($chapter->getUniquePhraseIds() as $phraseId) {
                    $uniquePhraseIds[intval($phraseId)] = true;
                }
            }

            $reviewWords = $reviewWords->whereIn('word', array_keys($uniqueWords));
            $reviewPhrases = $reviewPhrases->whereIn('id', array_keys($uniquePhraseIds));
        }

        $reviewWords = $reviewWords->inRandomOrder()->get();
        $reviewPhrases = $reviewPhrases->inRandomOrder()->get();

        // brush words and phrases together into one array
        $reviews = [];
        foreach ($reviewWords as $word) {
            $word->type = 'word';
            $reviews[] = $word; 
        }

        foreach ($reviewPhrases as $phrase) {
            $phrase->type = 'phrase';
            $reviews[] = $phrase;
        }

        return $reviews;
    }
}