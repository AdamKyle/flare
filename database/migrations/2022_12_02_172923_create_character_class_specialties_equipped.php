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
        Schema::create('character_class_specialties_equipped', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('game_class_special_id');
            $table->foreign('character_id', 'cc_se_c_id')->references('id')->on('characters');
            $table->foreign('game_class_special_id', 'cc_se_gcs_id')->references('id')->on('game_class_specials');
            $table->integer('level');
            $table->integer('current_xp');
            $table->integer('required_xp');
            $table->boolean('equipped');
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
        Schema::dropIfExists('character_class_specialties_equipped');
    }
};
