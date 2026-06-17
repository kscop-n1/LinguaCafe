<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

// models
use App\Models\Setting;

// services
use App\Services\GoalService;

class EncounteredWord extends Model
{
    use HasFactory;
    public const INVALID_VOCABULARY_SQL_PATTERNS = [
        "^[^[:alpha:]]",
        "[^[:alpha:]]$",
        "[0-9]",
        "[+*/=]",
        "--",
        "[_[:space:].,:;?!()\[\]{}<>|]",
        "^[+\\-]?[0-9]+([.,][0-9]+)?$",
        "^[+\\-]?[0-9]+d[0-9]*$",
        "^[+\\-]?[0-9]+(d[0-9]*)?(\\/[+\\-]?[0-9]+(d[0-9]*)?)+$",
        "^['’]s$",
    ];

    protected $fillable = [
        'user_id',
        'language',
        'stage',
        'word',
        'kanji',
        'reading',
        'base_word',
        'base_word_reading',
        'translation',
        'lookup_count',
        'read_count',
        'relearning',
        'created_at',
        'updated_at',
    ];

    public function scopeValidVocabularyToken(Builder $query): Builder {
        $query->whereRaw("word REGEXP ?", ["[[:alpha:]]"]);

        foreach (self::INVALID_VOCABULARY_SQL_PATTERNS as $pattern) {
            $query->whereRaw("word NOT REGEXP ?", [$pattern]);
        }

        return $query;
    }

    public function setStage($stage, $ignoreAchivement = false) {
       
        // if it's a newly saved word, update today's achievement
        if ($this->stage >= 0 && $stage < 0 && !$ignoreAchivement) {
            (new GoalService())->updateGoalAchievement($this->user_id, $this->language, 'learn_words', 1);
        }
        
        if ($this->stage >= 0 && $stage < 0 && $stage !== -7) {
            $this->relearning = true;
        }

        if ($stage >= 0) {
            $this->relearning = false;
        }

        $this->stage = $stage;
        $reviewIntervals = Setting::where('name', 'reviewIntervals')->first();
        $reviewIntervals = json_decode($reviewIntervals->value);

        // find the most optimal day for the next review
        if ($stage < 0) {
            $stageString = strval($stage);
            $possibleDates = $reviewIntervals->$stageString;
            $nextReviewIndex = 0;
            for ($i = 0; $i < count($possibleDates); $i++) {
                $data = new \stdClass();
                $data->date = Carbon::now()->addDays($possibleDates[$i])->toDateString();
                $data->count = EncounteredWord::where('user_id', $this->user_id)->where('next_review', $data->date)->count();
                $possibleDates[$i] = $data;

                if ($possibleDates[$i]->count < $possibleDates[$nextReviewIndex]->count) {
                    $nextReviewIndex = $i;
                }
            }
            
            $this->next_review = $possibleDates[$nextReviewIndex]->date;
            if (is_null($this->added_to_srs)) {
                $this->added_to_srs = Carbon::now()->toDateString();
            }
        } else {
            $this->next_review = null;
        }
    }
}
