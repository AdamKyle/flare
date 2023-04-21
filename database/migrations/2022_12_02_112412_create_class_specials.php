<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_class_specials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_class_id');
            $table->foreign('game_class_id', 'gc_specials')->references('id')->on('game_classes');
            $table->string('name');
            $table->text('description');
            $table->integer('requires_class_rank_level');
            $table->integer('specialty_damage')->nullable()->default(0);
            $table->integer('increase_specialty_damage_per_level')->nullable()->default(0);
            $table->decimal('specialty_damage_uses_damage_stat_amount', 8, 4)->nullable()->default(0);
            $table->decimal('base_damage_mod', 8, 4)->nullable()->default(0);
            $table->decimal('base_ac_mod', 8, 4)->nullable()->default(0);
            $table->decimal('base_healing_mod', 8, 4)->nullable()->default(0);
            $table->decimal('base_spell_damage_mod', 8, 4)->nullable()->default(0);
            $table->decimal('health_mod', 8, 4)->nullable()->default(0);
            $table->decimal('base_damage_stat_increase', 8, 4)->nullable()->default(0);
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
        Schema::dropIfExists('class_specials');
    }
};
