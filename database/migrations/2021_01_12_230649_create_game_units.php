<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description');
            $table->integer('attack');
            $table->integer('defence');
            $table->boolean('can_not_be_healed')->default(false);
            $table->boolean('is_settler')->default(false);
            $table->double('reduces_morale_by')->default(false);
            $table->boolean('can_heal')->default(false);
            $table->double('heal_percentage')->default(false);
            $table->boolean('siege_weapon')->default(false);
            $table->boolean('attacks_walls')->default(false);
            $table->boolean('attacks_buildings')->default(false);
            $table->boolean('defender')->default(false);
            $table->boolean('attacker')->default(false);
            $table->string('primary_target')->nullable();
            $table->string('fall_back')->nullable();
            $table->integer('travel_time');
            $table->integer('wood_cost');
            $table->integer('clay_cost');
            $table->integer('stone_cost');
            $table->integer('iron_cost');
            $table->integer('required_population');
            $table->integer('time_to_recruit');
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
        Schema::dropIfExists('game_units');
    }
}
