<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuideQuestIdToQuestsCompleted extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quests_completed', function (Blueprint $table) {
            $table->bigInteger('guide_quest_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quests_completed', function (Blueprint $table) {
            $table->dropColumn('guide_quest_id');
        });
    }
}
