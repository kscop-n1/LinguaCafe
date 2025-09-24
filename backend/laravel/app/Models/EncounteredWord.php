<?php

namespace App\Models;

use App\Enums\GoalTypeEnum;
use App\Helpers\Language\LanguageConfig;
use App\Services\GoalService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// models

// services
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounteredWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language',
        'stage',
        'word',
        'kanji',
        'reading',
        'lemma',
        'lemma_reading',
        'translation',
        'lookup_count',
        'read_count',
        'relearning',
        'created_at',
        'updated_at',
    ];

    // TODO: move to service
    // TODO: refactor
    public function setStage($stage, $ignoreAchivement = false)
    {
        if ($this->stage >= 0 && $stage < 0 && !$ignoreAchivement) {
            (new GoalService)->updateOrCreateTodaysGoalAchievement(
                $this->user,
                LanguageConfig::load($this->language),
                GoalTypeEnum::LEARN_WORDS,
                1
            );
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
                $data = new \stdClass;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
