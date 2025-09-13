<?php

namespace App\Queries;

use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VocabularySearchQuery
{
    // TODO: rewrite using proper eloquent functions
    // TODO: add type hinting to parameters and change userid and language to User and LanguageConfig types
    public function retrieve(
        $userId,
        $language,
        $text,
        $bookId,
        $chapterId,
        $stage,
        $phrases,
        $orderBy,
        $translation): Builder
    {
        $wordsToSkip = config('linguacafe.words_to_skip');

        // get words and phrases
        // from filtered chapters
        $filteredChapters = Chapter::where('user_id', $userId)->where('language', $language);
        $filteredWords = [];
        $filteredPhraseIds = [];
        if ($bookId !== -1) {
            $filteredChapters = $filteredChapters->where('book_id', $bookId);
        }

        if ($chapterId !== -1) {
            $filteredChapters = $filteredChapters->where('id', $chapterId);
        }

        $filteredChapters = $filteredChapters->get();

        if ($bookId !== -1) {
            foreach ($filteredChapters as $filteredChapter) {
                $chapter = Chapter::where('user_id', $userId)
                    ->where('id', $filteredChapter->id)
                    ->first();

                // add filtered phrase ids
                $filteredChapterWords = $chapter->getProcessedText();

                foreach ($filteredChapterWords as $filteredChapterWord) {
                    $filteredChapterWord->phrase_ids = $filteredChapterWord->phrase_ids;
                    foreach ($filteredChapterWord->phrase_ids as $phraseId) {
                        if (!in_array($phraseId, $filteredPhraseIds, true)) {
                            array_push($filteredPhraseIds, $phraseId);
                        }
                    }
                }

                // add filtered words
                $filteredChapterUniqueWords = json_decode($filteredChapter->unique_words);
                foreach ($filteredChapterUniqueWords as $filteredChapterUniqueWord) {
                    if (!in_array($filteredChapterUniqueWord, $filteredWords, true)) {
                        array_push($filteredWords, $filteredChapterUniqueWord);
                    }
                }
            }
        }

        // search for words and apply filters
        $wordSearch = EncounteredWord::select('id', 'lemma', 'word', DB::raw("'' AS words_searchable"), 'reading', 'lemma_reading', 'stage', 'translation', 'read_count', 'lookup_count', 'added_to_srs', DB::raw("'word' AS type"))->where('user_id', $userId)
            ->where('language', $language)
            ->whereNotIn('word', $wordsToSkip);

        if ($text !== 'anytext') {
            $wordSearch = $wordSearch->where(function ($query) use ($text) {
                $query->orWhere('word', 'like', '%' . $text . '%')
                    ->orWhere('reading', 'like', '%' . $text . '%');
            });
        }

        if ($bookId !== -1) {
            $wordSearch->whereIn('word', $filteredWords);
        }

        if ($stage !== -999) {
            $wordSearch = $wordSearch->where('stage', $stage);
        }

        if ($translation == 'not empty') {
            $wordSearch = $wordSearch->where('translation', '<>', '');
        }

        // search for phrases and apply filters
        $phraseSearch = Phrase::select('id', DB::raw("'' AS lemma"), 'words as word', 'words_searchable', 'reading', DB::raw("'' AS lemma_reading"), 'stage', 'translation', DB::raw('-1 AS read_count'), DB::raw('-1 AS lookup_count'), 'added_to_srs', DB::raw("'phrase' AS type"))
            ->where('user_id', $userId)
            ->where('language', $language);

        if ($text !== 'anytext') {
            $phraseSearch = $phraseSearch->where(function ($query) use ($text) {
                $query->orWhere('words_searchable', 'like', '%' . $text . '%')
                    ->orWhere('reading', 'like', '%' . $text . '%');
            });
        }

        if ($bookId !== -1) {
            $phraseSearch->whereIn('id', $filteredPhraseIds);
        }

        if ($stage !== -999) {
            $phraseSearch = $phraseSearch->where('stage', $stage);
        }

        if ($translation == 'not empty') {
            $phraseSearch = $phraseSearch->where('translation', '<>', '');
        }

        if ($phrases == 'only words') {
            $search = $wordSearch;
        } elseif ($phrases == 'only phrases') {
            $search = $phraseSearch;
        } else {
            $search = $wordSearch->union($phraseSearch);
        }

        if ($orderBy == 'words') {
            $search = $search->orderBy('word');
        }

        if ($orderBy == 'words desc') {
            $search = $search->orderBy('word', 'desc');
        }

        if ($orderBy == 'stage') {
            $search = $search->orderBy('stage');
        }

        if ($orderBy == 'stage desc') {
            $search = $search->orderBy('stage', 'desc');
        }

        $search = $search->orderBy('id')->orderBy('type');

        return $search;
    }
}
