<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildingsInQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buildings_in_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'biq_cid')
                  ->references('id')->on('characters');
            $table->unsignedBigInteger('kingdom_id');
            $table->foreign('kingdom_id', 'biq_king_id')
                  ->references('id')->on('kingdoms');
            $table->unsignedBigInteger('building_id');
            $table->foreign('building_id', 'biq_build_id')
                  ->references('id')->on('buildings');
            $table->integer('to_level');
            $table->dateTime('completed_at');
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
        Schema::dropIfExists('buildings_in_queue');
    }
}
