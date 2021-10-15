<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxLevelConfigurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('max_level_configurations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('max_level');
            $table->bigInteger('half_way');
            $table->bigInteger('three_quarters');
            $table->bigInteger('last_leg');
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
        Schema::dropIfExists('max_level_configurations');
    }
}
