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
    public function up() {
        Schema::create('character_class_ranks_weapon_masteries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_class_rank_id');
            $table->foreign('character_class_rank_id', 'ccrank_id')->references('id')->on('character_class_ranks');
            $table->integer('weapon_type');
            $table->integer('current_xp');
            $table->integer('required_xp');
            $table->integer('level');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('class_ranks_weapon_masteries');
    }
};
