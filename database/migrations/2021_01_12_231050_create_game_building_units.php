<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameKingdomBuildingUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_building_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('game_building_id')->unsigned();
            $table->foreign('game_building_id', 'gu_game_building_id')
                  ->references('id')->on('game_buildings');
            $table->bigInteger('game_unit_id')->unsigned();
            $table->foreign('game_unit_id', 'gu_game_unit_id')
                  ->references('id')->on('game_units');
            $table->integer('required_level');
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
        Schema::dropIfExists('game_building_units');
    }
}
