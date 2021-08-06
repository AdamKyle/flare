<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePercisionOnSkillBonuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_skills', function (Blueprint $table) {
            $table->decimal('base_damage_mod_bonus_per_level', 10, 6)->change();
            $table->decimal('base_healing_mod_bonus_per_level', 10, 6)->change();
            $table->decimal('base_ac_mod_bonus_per_level', 10, 6)->change();
            $table->decimal('fight_time_out_mod_bonus_per_level', 10, 6)->change();
            $table->decimal('move_time_out_mod_bonus_per_level', 10, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_skills', function (Blueprint $table) {
            $table->decimal('base_damage_mod_bonus_per_level', 5, 4)->change();
            $table->decimal('base_healing_mod_bonus_per_level', 5, 4)->change();
            $table->decimal('base_ac_mod_bonus_per_level', 5, 4)->change();
            $table->decimal('fight_time_out_mod_bonus_per_level', 5, 4)->change();
            $table->decimal('move_time_out_mod_bonus_per_level', 5, 4)->change();
        });
    }
}
