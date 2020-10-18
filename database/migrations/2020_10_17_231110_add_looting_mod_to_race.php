<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLootingModToRace extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_races', function (Blueprint $table) {
            $table->float('looting_mod', 5, 4)->nullable()->default(0.0);
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
            $table->dropColumn('looting_mod');
        });
    }
}
