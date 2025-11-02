<?php

namespace App\Services;

use App\Helpers\Language\LanguageConfig;
use App\Models\EncounteredWord;
use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\User;

class StatisticsService
{
    public function __construct() {}

    // TODO: should be refactored to return a DTO
    public function getStatistics(User $user, LanguageConfig $language): \stdClass
    {
        $languageStatistics = new \stdClass();

        $readingGoal = Goal::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('type', 'read_words')
            ->first();

        $languageStatistics->days = new \stdClass();
        $languageStatistics->days->name = 'Days of activity';
        $languageStatistics->days->icon = config('app.frontend_build') === 'vue3' ? 'i-lucide-calendar' : 'mdi-calendar-check';
        $languageStatistics->days->value = GoalAchievement::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('achieved_quantity', '<>', 0)
            ->distinct('day')
            ->count('day');

        $languageStatistics->readWordCount = new \stdClass();
        $languageStatistics->readWordCount->name = 'Read words';
        $languageStatistics->readWordCount->icon = config('app.frontend_build') === 'vue3' ? 'i-lucide-book-open-text' : 'mdi-book-open-variant';
        $languageStatistics->readWordCount->value = GoalAchievement::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->where('goal_id', $readingGoal->id)
            ->sum('achieved_quantity');

        if ($language->name == 'japanese') {
            // get unique kanji
            $uniqueKanji = [];
            $words = EncounteredWord::query()
                ->where('stage', '<=', 0)
                ->where('language', 'japanese')
                ->where('user_id', $user->id)
                ->get();

            foreach ($words as $word) {
                $kanji = preg_split('//u', $word->kanji, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($kanji as $currentKanji) {
                    if (!in_array($currentKanji, $uniqueKanji, true)) {
                        array_push($uniqueKanji, $currentKanji);
                    }
                }
            }

            $languageStatistics->kanji = new \stdClass();
            $languageStatistics->kanji->name = 'Kanji';
            $languageStatistics->kanji->value = count($uniqueKanji);
            $languageStatistics->kanji->icon = 'mdi-ideogram-cjk';
        }

        $languageStatistics->known = new \stdClass();
        $languageStatistics->known->name = 'Known words';
        $languageStatistics->known->icon = config('app.frontend_build') === 'vue3' ? 'i-lucide-book-a' : 'mdi-credit-card-check';
        $languageStatistics->known->value = EncounteredWord::select('id')->where('stage', 0)
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->count('id');

        $languageStatistics->learning = new \stdClass();
        $languageStatistics->learning->name = 'Words currently studied';
        $languageStatistics->learning->icon = config('app.frontend_build') === 'vue3' ? 'i-lucide-graduation-cap' : 'mdi-school';
        $languageStatistics->learning->value = EncounteredWord::select('id')
            ->where('stage', '<', 0)
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->count('id');

        $languageStatistics->knownLemmas = new \stdClass();
        $languageStatistics->knownLemmas->name = 'Known lemmas';
        $languageStatistics->knownLemmas->icon = config('app.frontend_build') === 'vue3' ? 'i-lucide-square-split-horizontal' : 'mdi-alpha-l-box';
        $languageStatistics->knownLemmas->value = EncounteredWord::select('lemma')
            ->where('stage', 0)
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->groupBy('lemma')
            ->having('lemma', '!=', '')
            ->get()->count();

        return $languageStatistics;
    }
}
