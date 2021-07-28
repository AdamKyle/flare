<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGameMapWithBonuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->decimal('xp_bonus', 5, 4)->nullable();
            $table->decimal('skill_training_bonus', 5, 4)->nullable();
            $table->decimal('drop_chance_bonus', 5, 4)->nullable();
            $table->decimal('enemy_stat_bonus', 5, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->dropColumn('xp_bonus');
            $table->dropColumn('skill_training_bonus');
            $table->dropColumn('drop_chance_bonus');
            $table->dropColumn('enemy_stat_bonus');
        });
    }
}
