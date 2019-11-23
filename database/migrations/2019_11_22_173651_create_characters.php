<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('game_race_id')->unsigned();
            $table->bigInteger('game_class_id')->unsigned();
            $table->foreign('game_race_id')
                ->references('id')->on('game_races');
            $table->foreign('game_class_id')
                ->references('id')->on('game_classes');
            $table->foreign('user_id')
                ->references('id')->on('users');
            $table->string('name');
            $table->string('damage_stat');
            $table->bigInteger('xp');
            $table->bigInteger('xp_next');
            $table->bigInteger('str');
            $table->bigInteger('dur');
            $table->bigInteger('dex');
            $table->bigInteger('chr');
            $table->bigInteger('int');
            $table->bigInteger('ac');
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
        Schema::dropIfExists('characters');
    }
}
