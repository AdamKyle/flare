<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCelestialFights extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('celestial_fights', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('monster_id')->unsigned()->nullable();
            $table->foreign('monster_id')
                  ->references('id')->on('monsters');
            $table->bigInteger('character_id')->unsigned()->nullable();
            $table->foreign('character_id')
                ->references('id')->on('characters');
            $table->date('conjured_at');
            $table->integer('x_position');
            $table->integer('y_position');
            $table->boolean('damaged_kingdom')->default(false);
            $table->boolean('stole_treasury')->default(false);
            $table->boolean('weakened_morale')->default(false);
            $table->integer('current_health');
            $table->integer('max_health');
            $table->integer('type');
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
        Schema::dropIfExists('celestial_fights');
    }
}
