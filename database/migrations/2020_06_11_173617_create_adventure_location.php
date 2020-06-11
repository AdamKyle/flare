<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdventureLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adventure_location', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('adventure_id')->unsigned();
            $table->bigInteger('location_id')->unsigned();
            $table->foreign('adventure_id')
                  ->references('id')->on('adventures');
            $table->foreign('location_id')
                  ->references('id')->on('locations');
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
        Schema::dropIfExists('adventure_location');
    }
}
