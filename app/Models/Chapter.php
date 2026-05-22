<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Phrase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    function getProcessedText() {
        if (empty($this->processed_text)) {
            return [];
        }

        $decompressedText = gzuncompress($this->processed_text);
        if ($decompressedText === false) {
            return [];
        }

        return json_decode($decompressedText) ?: [];
    }

    function setProcessedText($processedText) {
        $this->processed_text = gzcompress(json_encode($processedText), 1);
    }

    function getUniquePhraseIds() {
        return json_decode($this->unique_phrase_ids) ?: [];
    }

    function refreshUniquePhraseIds() {
        $phraseIds = [];

        foreach ($this->getProcessedText() ?: [] as $word) {
            foreach (($word->phrase_ids ?? []) as $phraseId) {
                $phraseIds[intval($phraseId)] = true;
            }
        }

        $this->unique_phrase_ids = json_encode(array_keys($phraseIds));
    }

    function getWordCounts($words) {
        $uniqueWordIds = json_decode($this->unique_word_ids) ?: [];
        $wordCounts = new \stdClass();
        $wordCounts->total = $this->word_count;
        $wordCounts->unique = count($uniqueWordIds);
        $wordCounts->known = 0;
        $wordCounts->highlighted = 0;
        $wordCounts->new = 0;
        
        foreach($uniqueWordIds as $wordId) {
            if (!isset($words[$wordId])) {
                continue;
            }

            if ($words[$wordId]['stage'] < 0) {
                $wordCounts->highlighted ++;
            }

            if ($words[$wordId]['stage'] == 0) {
                $wordCounts->known ++;
            }

            if ($words[$wordId]['stage'] == 2) {
                $wordCounts->new ++;
            }
        }

        return $wordCounts;
    }
}
