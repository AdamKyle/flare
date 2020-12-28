<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKingdoms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kingdoms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'king_cid')
                  ->references('id')->on('characters');
            $table->unsignedBigInteger('game_map_id');
            $table->foreign('game_map_id', 'king_gmid')
                ->references('id')->on('game_maps');
            $table->string('name');
            $table->json('color');
            $table->bigInteger('stone');
            $table->bigInteger('wood');
            $table->bigInteger('clay');
            $table->bigInteger('iron');
            $table->integer('current_population');
            $table->integer('max_population');
            $table->integer('x_position');
            $table->integer('y_position');
            $table->double('morale');
            $table->integer('treasury')->nullable()->default(0);
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
        Schema::dropIfExists('kingdoms');
    }
}
