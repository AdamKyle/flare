<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('character_id')->unsigned()->nullable();
            $table->foreign('character_id')
                ->references('id')->on('characters');
            $table->bigInteger('monster_id')->unsigned()->nullable();
            $table->foreign('monster_id')
                ->references('id')->on('monsters');
            $table->string('description');
            $table->string('name');
            $table->boolean('currently_training')->nullable()->default(false);
            $table->integer('level');
            $table->integer('max_level');
            $table->integer('xp')->nullable();
            $table->integer('xp_max')->nullable();
            $table->decimal('xp_towards')->nullable();
            $table->decimal('base_damage_mod')->nullable();
            $table->decimal('base_healing_mod')->nullable();
            $table->decimal('base_ac_mod')->nullable();
            $table->decimal('fight_time_out_mod')->nullable();
            $table->decimal('move_time_out_mod')->nullable();
            $table->boolean('can_train')->nullable()->default(true);
            $table->decimal('skill_bonus');
            $table->decimal('skill_bonus_per_level');
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
        Schema::dropIfExists('skills');
    }
}
