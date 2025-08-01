<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'name',
        'read_count',
        'word_count',
        'language',
        'raw_text',
    ];

    public function getProcessedText()
    {
        return json_decode(gzuncompress($this->processed_text));
    }

    public function setProcessedText($processedText)
    {
        $this->processed_text = gzcompress(json_encode($processedText), 1);
    }

    public function getWordCounts($words)
    {
        $uniqueWordIds = json_decode($this->unique_word_ids);
        $wordCounts = new \stdClass;
        $wordCounts->total = $this->word_count;
        $wordCounts->unique = count($uniqueWordIds);
        $wordCounts->known = 0;
        $wordCounts->highlighted = 0;
        $wordCounts->new = 0;

        foreach ($uniqueWordIds as $wordId) {
            if ($words[$wordId]['stage'] < 0) {
                $wordCounts->highlighted++;
            }

            if ($words[$wordId]['stage'] == 0) {
                $wordCounts->known++;
            }

            if ($words[$wordId]['stage'] == 2) {
                $wordCounts->new++;
            }
        }

        return $wordCounts;
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
}
