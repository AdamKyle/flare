<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNpcs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('game_map_id')->unsigned()->nullable();
            $table->foreign('game_map_id')
                  ->references('id')->on('game_maps');
            $table->string('name')->unique();
            $table->string('real_name');
            $table->integer('type');
            $table->boolean('moves_around_map')->default(false);
            $table->boolean('must_be_at_same_location')->default(false);
            $table->string('text_command_to_message');
            $table->integer('x_position');
            $table->integer('y_position');
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
        Schema::dropIfExists('npcs');
    }
}
