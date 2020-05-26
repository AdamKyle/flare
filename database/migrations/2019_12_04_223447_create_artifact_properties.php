<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtifactProperties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artifact_properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('base_damage_mod')->nullable();
            $table->decimal('base_healing_mod')->nullable();
            $table->decimal('str_mod')->nullable();
            $table->decimal('dur_mod')->nullable();
            $table->decimal('dex_mod')->nullable();
            $table->decimal('chr_mod')->nullable();
            $table->decimal('int_mod')->nullable();
            $table->decimal('ac_mod')->nullable();
            $table->string('skill_name')->nullable();
            $table->decimal('skill_training_bonus')->nullable();
            $table->string('description');
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
        Schema::dropIfExists('artifiact_properties');
    }
}
