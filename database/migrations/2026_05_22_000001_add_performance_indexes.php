<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        Schema::table('encountered_words', function (Blueprint $table) {
            $table->index(['user_id', 'language', 'word'], 'encountered_words_user_language_word_idx');
            $table->index(['user_id', 'language', 'stage'], 'encountered_words_user_language_stage_idx');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->index(['user_id', 'book_id', 'processing_status'], 'chapters_user_book_status_idx');
            $table->index(['user_id', 'language', 'processing_status'], 'chapters_user_language_status_idx');
        });

        Schema::table('phrases', function (Blueprint $table) {
            $table->index(['user_id', 'language', 'stage'], 'phrases_user_language_stage_idx');
        });
    }

    public function down()
    {
        Schema::table('phrases', function (Blueprint $table) {
            $table->dropIndex('phrases_user_language_stage_idx');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex('chapters_user_language_status_idx');
            $table->dropIndex('chapters_user_book_status_idx');
        });

        Schema::table('encountered_words', function (Blueprint $table) {
            $table->dropIndex('encountered_words_user_language_stage_idx');
            $table->dropIndex('encountered_words_user_language_word_idx');
        });
    }
}
