<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::query()
            ->where('password_changed', false)
            ->update(['password_changed' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::query()
            ->where('password_changed', true)
            ->update(['password_changed' => false]);
    }
};
