<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdventureFloorDescriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adventure_floor_descriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('adventure_id')->unsigned();
            $table->foreign('adventure_id')
                  ->references('id')->on('adventures');
            $table->text('description');
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
        Schema::dropIfExists('adventure_floor_descriptions');
    }
}
