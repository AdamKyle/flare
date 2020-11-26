<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemAffixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_affixes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('base_damage_mod', 5, 4)->nullable();
            $table->decimal('base_healing_mod', 5, 4)->nullable();
            $table->decimal('base_ac_mod', 5, 4)->nullable();
            $table->decimal('str_mod', 5, 4)->nullable();
            $table->decimal('dur_mod', 5, 4)->nullable();
            $table->decimal('dex_mod', 5, 4)->nullable();
            $table->decimal('chr_mod', 5, 4)->nullable();
            $table->decimal('int_mod', 5, 4)->nullable();
            $table->integer('int_required')->nullable()->default(1);
            $table->integer('skill_level_required')->nullable();
            $table->integer('skill_level_trivial')->nullable();
            $table->string('skill_name')->nullable();
            $table->decimal('skill_training_bonus', 5, 4)->nullable();
            $table->integer('cost')->default(0);
            $table->string('type');
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
        Schema::dropIfExists('item_affixes');
    }
}
