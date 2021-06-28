<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('description');
            $table->string('name');
            $table->integer('max_level');
            $table->decimal('base_damage_mod_bonus_per_level', 5, 4)->nullable();
            $table->decimal('base_healing_mod_bonus_per_level', 5, 4)->nullable();
            $table->decimal('base_ac_mod_bonus_per_level', 5, 4)->nullable();
            $table->decimal('fight_time_out_mod_bonus_per_level', 5, 4)->nullable();
            $table->decimal('move_time_out_mod_bonus_per_level', 5, 4)->nullable();
            $table->boolean('can_train')->default(false);
            $table->decimal('skill_bonus_per_level', 5, 4)->nullable();
            $table->boolean('specifically_assigned')->default(false);
            $table->boolean('can_monsters_have_skill')->default(false);
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
        Schema::dropIfExists('game_skills');
    }
}
