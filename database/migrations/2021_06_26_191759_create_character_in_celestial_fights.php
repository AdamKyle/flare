<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterInCelestialFights extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_in_celestial_fights', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('celestial_fight_id')->unsigned();
            $table->foreign('celestial_fight_id')
                ->references('id')->on('celestial_fights');
            $table->bigInteger('character_id')->unsigned();
            $table->foreign('character_id')
                ->references('id')->on('characters');
            $table->integer('character_max_health');
            $table->integer('character_current_health');
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
        Schema::dropIfExists('character_in_celestial_fights');
    }
}
