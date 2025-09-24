<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('encountered_words', function (Blueprint $table) {
            $table->dropColumn('lemma');
        });

        Schema::table('encountered_words', function (Blueprint $table) {
            $table->renameColumn('base_word', 'lemma');
        });

        Schema::table('encountered_words', function (Blueprint $table) {
            $table->renameColumn('base_word_reading', 'lemma_reading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encountered_words', function (Blueprint $table) {
            $table->renameColumn('lemma', 'base_word');
        });

        Schema::table('encountered_words', function (Blueprint $table) {
            $table->renameColumn('lemma_reading', 'base_word_reading');
        });

        Schema::table('encountered_words', function (Blueprint $table) {
            $table->string('lemma');
        });
    }
};
