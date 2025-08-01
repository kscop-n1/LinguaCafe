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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('language');
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('chapter_id');

            // later on users will be able to add custom bookmarks inside the text.
            $table->string('name', length: 128)->nullable();
            $table->string('color', length: 6)->nullable();
            $table->unsignedBigInteger('word_index')->nullable();
            $table->enum('type', ['next_chapter', 'custom'])->default('custom');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
