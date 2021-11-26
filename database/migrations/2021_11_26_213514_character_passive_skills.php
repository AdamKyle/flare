<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CharacterPassiveSkills extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('character_passive_skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->foreign('character_id', 'c_cps')
                  ->references('id')->on('characters');
            $table->unsignedBigInteger('passive_skill_id');
            $table->foreign('passive_skill_id', 'ps_psk')
                  ->references('id')->on('passive_skills');
            $table->integer('current_level')->nullable()->default(0);
            $table->integer('hours_to_next')->nullable()->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->boolean('is_locked')->default(true);
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
        Schema::dropIfExists('character_passive_skills');
    }
}
