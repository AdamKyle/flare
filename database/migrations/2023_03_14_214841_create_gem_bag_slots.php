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
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('gem_bag_id');
            $table->foreign('gem_bag_id')->references('gem_bags')->on('id');
            $table->unsignedBigInteger('gem_id');
            $table->foreign('gem_id')->references('gems')->on('id');
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
