<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewStatModsToRacesAndClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_races', function (Blueprint $table) {
            $table->renameColumn('deffense_mod', 'defense_mod');
            $table->integer('agi_mod')->nullable()->default(0);
            $table->integer('focus_mod')->nullable()->default(0);
        });

        Schema::table('game_classes', function (Blueprint $table) {
            $table->integer('agi_mod')->nullable()->default(0);
            $table->integer('focus_mod')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_races', function (Blueprint $table) {
            $table->renameColumn('defense_mod', 'deffense_mod');
            $table->dropColumn('agi_mod');
            $table->dropColumn('focus_mod');
        });

        Schema::table('game_classes', function (Blueprint $table) {
            $table->dropColumn('agi_mod');
            $table->dropColumn('focus_mod');
        });
    }
}
