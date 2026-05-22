<?php

use App\Models\Chapter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniquePhraseIdsToChapters extends Migration
{
    public function up()
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->text('unique_phrase_ids')->nullable()->after('unique_word_ids');
        });

        Chapter::query()
            ->select(['id', 'processed_text'])
            ->whereNotNull('processed_text')
            ->orderBy('id')
            ->chunkById(100, function ($chapters) {
                foreach ($chapters as $chapter) {
                    $phraseIds = [];

                    foreach ($chapter->getProcessedText() ?: [] as $word) {
                        foreach (($word->phrase_ids ?? []) as $phraseId) {
                            $phraseIds[intval($phraseId)] = true;
                        }
                    }

                    DB::table('chapters')
                        ->where('id', $chapter->id)
                        ->update(['unique_phrase_ids' => json_encode(array_keys($phraseIds))]);
                }
            });
    }

    public function down()
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('unique_phrase_ids');
        });
    }
}
