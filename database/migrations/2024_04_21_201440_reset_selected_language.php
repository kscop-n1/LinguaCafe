<?php

use App\Helpers\Language\LanguageConfig;
use App\Models\User;
use App\Services\GoalService;
use Illuminate\Database\Migrations\Migration;

class ResetSelectedLanguage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $goalService = new GoalService;
        $users = User::get();

        foreach ($users as $user) {
            $user->selected_language = 'spanish';
            $user->save();

            $goalService->createGoalsForLanguage($user, LanguageConfig::load('spanish'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
