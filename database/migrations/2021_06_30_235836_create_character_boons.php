<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterBoons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_boons', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('character_id')->unsigned()->nullable();
            $table->foreign('character_id')
                ->references('id')->on('characters');
            $table->integer('type');
            $table->decimal('stat_bonus', 8, 4)->nullable()->default(0.0);
            $table->integer('affect_skill_type')->nullable();
            $table->decimal('skill_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('base_damage_mod_bonus', 8, 4)->nullable();
            $table->decimal('base_healing_mod_bonus', 8, 4)->nullable();
            $table->decimal('base_ac_mod_bonus', 8, 4)->nullable();
            $table->decimal('fight_time_out_mod_bonus', 8, 4)->nullable();
            $table->decimal('move_time_out_mod_bonus', 8, 4)->nullable();
            $table->decimal('skill_training_bonus', 8 ,4)->nullable()->default(0.0);
            $table->dateTime('started');
            $table->dateTime('complete');
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
        Schema::dropIfExists('character_boons');
    }
}
