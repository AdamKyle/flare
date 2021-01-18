<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitsInQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units_in_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'uiq_cid')
                  ->references('id')->on('characters');
            $table->unsignedBigInteger('kingdom_id');
            $table->foreign('kingdom_id', 'uiq_king_id')
                  ->references('id')->on('kingdoms');
            $table->unsignedBigInteger('game_unit_id');
            $table->foreign('game_unit_id', 'uiq_game_unit_id')
                  ->references('id')->on('game_units');
            $table->integer('amount');
            $table->dateTime('completed_at');
            $table->dateTime('started_at');
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
        Schema::dropIfExists('units_in_queue');
    }
}
