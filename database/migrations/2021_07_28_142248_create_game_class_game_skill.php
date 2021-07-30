<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameClassGameSkill extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_class_game_skill', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('game_class_id')->unsigned();
            $table->foreign('game_class_id')
                ->references('id')->on('game_classes');
            $table->bigInteger('game_skill_id')->unsigned();
            $table->foreign('game_skill_id')
                ->references('id')->on('game_skills');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_class_game_skill');
    }
}
