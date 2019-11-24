<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monsters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->bigInteger('str');
            $table->bigInteger('dur');
            $table->bigInteger('dex');
            $table->bigInteger('chr');
            $table->bigInteger('int');
            $table->bigInteger('ac');
            $table->string('damage_stat');
            $table->integer('xp');
            $table->string('health_range');
            $table->string('attack_range');
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
        Schema::dropIfExists('monsters');
    }
}
