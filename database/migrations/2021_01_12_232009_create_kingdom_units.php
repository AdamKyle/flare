<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKingdomUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kingdom_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('kingdom_id')->unsigned();
            $table->foreign('kingdom_id', 'ku_kingdom_id')
                  ->references('id')->on('kingdoms');
            $table->bigInteger('game_unit_id');
            $table->foreign('game_unit_id', 'ku_game_unit_id')
                  ->references('id')->on('game_units');
            $table->integer('amount');
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
        Schema::dropIfExists('kingdom_units');
    }
}
