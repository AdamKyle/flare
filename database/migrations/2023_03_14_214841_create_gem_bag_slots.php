<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gem_bag_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('gem_bag_id');
            $table->foreign('gem_bag_id')->references('id')->on('gem_bags');
            $table->unsignedBigInteger('gem_id');
            $table->foreign('gem_id')->references('id')->on('gems');
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
        Schema::dropIfExists('gem_bag_slots');
    }
};
