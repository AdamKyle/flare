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
            $table->string('name');
            $table->boolean('currently_training')->nullable()->default(false);
            $table->integer('level');
            $table->integer('xp');
            $table->integer('xp_max');
            $table->integer('skill_bonus');
            $table->integer('skill_bonus_per_level');
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
