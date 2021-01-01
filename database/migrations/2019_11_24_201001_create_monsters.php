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
            $table->Integer('str');
            $table->Integer('dur');
            $table->Integer('dex');
            $table->Integer('chr');
            $table->Integer('int');
            $table->Integer('ac');
            $table->Integer('max_level')->nullable()->default(0);
            $table->string('damage_stat');
            $table->integer('xp');
            $table->decimal('drop_check', 5, 4);
            $table->integer('gold')->nullable()->default(10);
            $table->string('health_range');
            $table->string('attack_range');
            $table->boolean('published')->nullable()->default(true);
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
