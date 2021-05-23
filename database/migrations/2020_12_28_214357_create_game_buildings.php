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
            $table->text('description');
            $table->integer('max_level');
            $table->integer('base_durability');
            $table->integer('base_defence');
            $table->integer('required_population');
            $table->integer('units_per_level')->nullable();
            $table->integer('only_at_level')->nullable();
            $table->boolean('is_resource_building')->default(false);
            $table->boolean('trains_units')->default(false);
            $table->boolean('is_walls')->default(false);
            $table->boolean('is_church')->default(false);
            $table->boolean('is_farm')->default(false);
            $table->integer('wood_cost')->default(0);
            $table->integer('clay_cost')->default(0);
            $table->integer('stone_cost')->default(0);
            $table->integer('iron_cost')->default(0);
            $table->double('time_to_build')->default(1);
            $table->double('time_increase_amount')->default(0);
            $table->double('decrease_morale_amount')->default(0);
            $table->integer('increase_population_amount')->default(0);
            $table->double('increase_morale_amount')->default(0);
            $table->double('increase_wood_amount')->default(0);
            $table->double('increase_clay_amount')->default(0);
            $table->double('increase_stone_amount')->default(0);
            $table->double('increase_iron_amount')->default(0);
            $table->double('increase_durability_amount')->default(0);
            $table->double('increase_defence_amount')->default(0);
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
