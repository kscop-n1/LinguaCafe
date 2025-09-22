<?php

namespace App\Services\Vocabulary;

use App\DataTransferObjects\Vocabulary\KanjiData;
use App\DataTransferObjects\Vocabulary\KanjiSearchResultData;
use App\Helpers\Language\LanguageConfig;
use App\Models\EncounteredWord;
use App\Models\Kanji;
use App\Models\Radical;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VocabularyKanjiService
{
    // TODO: rewrite with proper eloquent functions
    public function searchKanji(User $user, LanguageConfig $language, string $groupBy, bool $showUnknown): KanjiSearchResultData
    {
        $words = EncounteredWord::query()
            ->where('user_id', $user->id)
            ->where('stage', 0)
            ->where('language', $language->name)
            ->where('kanji', '<>', '')
            ->get();

        // get knwon kanji
        $knownKanji = [];
        foreach ($words as $word) {
            $wordKanji = preg_split('//u', $word->kanji, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($wordKanji as $currentKanji) {
                if (!in_array($currentKanji, $knownKanji, true)) {
                    array_push($knownKanji, $currentKanji);
                }
            }
        }

        // get kanji list
        if ($groupBy == 'grade') {
            $kanji = Kanji::where(function ($query) use ($knownKanji) {
                $query->where('grade', '>', 0)->orWhereIn('kanji', $knownKanji);
            });
        } else {
            $kanji = Kanji::where(function ($query) use ($knownKanji) {
                $query->where('jlpt', '>', 0)->orWhereIn('kanji', $knownKanji);
            });
        }

        if (!$showUnknown) {
            $kanji = $kanji->whereIn('kanji', $knownKanji);
        }

        $kanji = $kanji->get();

        // label kanji list
        foreach ($kanji as $currentKanji) {
            $currentKanji->known = in_array($currentKanji->kanji, $knownKanji);
        }

        // group kanji list
        if ($groupBy == 'grade') {
            $kanji = $kanji->groupBy('grade');
        } else {
            $kanji = $kanji->groupBy('jlpt');
        }

        // get count for statistics
        if ($groupBy == 'grade') {
            $totalCount = Kanji::select('grade', DB::raw('count(id) as total'))
                ->groupBy('grade')
                ->get()
                ->keyBy('grade');

            $knownCount = Kanji::select('grade', DB::raw('count(id) as total'))
                ->whereIn('kanji', $knownKanji)->groupBy('grade')
                ->get()
                ->keyBy('grade');
        } else {
            $totalCount = Kanji::select('jlpt', DB::raw('count(id) as total'))
                ->groupBy('jlpt')
                ->get()
                ->keyBy('jlpt');

            $knownCount = Kanji::select('jlpt', DB::raw('count(id) as total'))
                ->whereIn('kanji', $knownKanji)->groupBy('jlpt')
                ->get()
                ->keyBy('jlpt');
        }

        return new KanjiSearchResultData(
            $kanji,
            $totalCount,
            $knownCount
        );
    }

    public function getKanjiDetails(User $user, string $kanjiCharacter): KanjiData
    {
        $kanjiData = Kanji::query()
            ->where('kanji', '=', $kanjiCharacter)
            ->firstOrFail();

        $words = EncounteredWord::query()
            ->whereLike('word', '%' . $kanjiCharacter . '%')
            ->where('user_id', $user->id)
            ->limit(12)
            ->get();

        $radicals = Radical::query()
            ->select(['radicals'])
            ->where('kanji', '=', $kanjiCharacter)
            ->first();

        return new KanjiData(
            kanji: $kanjiData,
            words: $words,
            radicals: $radicals?->radicals
        );
    }
}
