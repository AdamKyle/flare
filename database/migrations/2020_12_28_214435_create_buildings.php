<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_building_id');
            $table->foreign('game_building_id', 'builds_gbid')
                  ->references('id')->on('game_buildings');
            $table->unsignedBigInteger('kingdoms_id');
            $table->foreign('kingdoms_id', 'buildsings_kid')
                ->references('id')->on('kingdoms');
            $table->integer('level');
            $table->integer('max_defence');
            $table->integer('max_durability');
            $table->integer('current_defence');
            $table->integer('current_durability');
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
        Schema::dropIfExists('buildings');
    }
}
