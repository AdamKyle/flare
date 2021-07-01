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
            $table->decimal('affected_skill_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('affected_skill_training_bonus', 8 ,4)->nullable()->default(0.0);
            $table->decimal('affected_skill_base_damage_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('affected_skill_base_healing_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('affected_skill_base_ac_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('affected_skill_fight_time_out_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->decimal('affected_skill_move_time_out_mod_bonus', 8, 4)->nullable()->default(0.0);
            $table->date('started');
            $table->date('complete');
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
