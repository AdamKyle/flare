<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddToHitStatToGameClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_classes', function (Blueprint $table) {
            $table->renameColumn('deffense_mod', 'defense_mod');
            $table->string('to_hit_stat')->after('damage_stat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_classes', function (Blueprint $table) {
            $table->renameColumn('defense_mod', 'deffense_mod');
            $table->dropColumn('to_hit_stat');
        });
    }
}
