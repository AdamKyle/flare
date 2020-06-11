<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdventures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adventures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->bigInteger('reward_item_id')->unsigned();
            $table->integer('levels');
            $table->integer('time_per_level');
            $table->float('gold_rush_chance')->nullable()->default(0.0);
            $table->float('item_find_chance')->nullable()->default(0.0);
            $table->float('skill_exp_bonus')->nullable()->default(0.0);
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
        Schema::dropIfExists('adventures');
    }
}
