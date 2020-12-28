<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameBuildings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_buildings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description');
            $table->integer('max_level');
            $table->integer('base_durability');
            $table->integer('base_defence');
            $table->integer('required_population');
            $table->boolean('is_walls')->nullable()->default(false);
            $table->boolean('is_church')->nullable()->default(false);
            $table->boolean('is_farm')->nullable()->default(false);
            $table->integer('wood_cost')->nullable()->default(0);
            $table->integer('clay_cost')->nullable()->default(0);
            $table->integer('stone_cost')->nullable()->default(0);
            $table->integer('iron_cost')->nullable()->default(0);
            $table->integer('increase_population_amount')->nullable()->default(0);
            $table->double('increase_morale_amount')->nullable()->default(0);
            $table->double('increase_wood_amount')->nullable()->default(0);
            $table->double('increase_clay_amount')->nullable()->default(0);
            $table->double('increase_stone_amount')->nullable()->default(0);
            $table->double('increase_iron_amount')->nullable()->default(0);
            $table->double('increase_durability_amount')->nullable()->default(0);
            $table->double('increase_defence_amount')->nullable()->default(0);
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
        Schema::dropIfExists('game_buildings');
    }
}
