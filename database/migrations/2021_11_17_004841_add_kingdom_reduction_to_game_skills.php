<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKingdomReductionToGameSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_skills', function (Blueprint $table) {
            $table->decimal('unit_time_reduction', 8, 4)->nullable()->default(0);
            $table->decimal('building_time_reduction', 8, 4)->nullable()->default(0);
            $table->decimal('unit_movement_time_reduction', 8, 4)->nullable()->default(0);
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
            $table->dropColumn('unit_time_reduction');
            $table->dropColumn('building_time_reduction');
            $table->dropColumn('unit_movement_time_reduction');
        });
    }
}
