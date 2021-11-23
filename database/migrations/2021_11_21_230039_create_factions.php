<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'ca_fid')
                ->references('id')->on('characters');
            $table->unsignedBigInteger('game_map_id');
            $table->foreign('game_map_id', 'gmi_gm')
                ->references('id')->on('game_maps');
            $table->integer('current_level')->nullable()->default(0);
            $table->integer('current_points')->nullable()->default(0);
            $table->integer('points_needed')->nullable()->default(0);
            $table->string('title')->nullable();
            $table->boolean('maxed')->default(false);
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
        Schema::dropIfExists('factions');
    }
}
