<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameSkillIdToSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->bigInteger('game_skill_id')->unsigned()->default(0);
            $table->foreign('game_skill_id')
                  ->references('id')->on('game_skills');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropForeign('skills_game_skill_id_foreign');
            $table->dropColumn('game_skill_id');
        });
    }
}
