<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_classes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('damage_stat');
            $table->integer('str_mod')->nullable()->default(0);
            $table->integer('dur_mod')->nullable()->default(0);
            $table->integer('dex_mod')->nullable()->default(0);
            $table->integer('chr_mod')->nullable()->default(0);
            $table->integer('int_mod')->nullable()->default(0);
            $table->decimal('accuracy_mod', 5, 4)->nullable()->default(0);
            $table->decimal('dodge_mod', 5, 4)->nullable()->default(0);
            $table->decimal('deffense_mod', 5, 4)->nullable()->default(0);
            $table->decimal('looting_mod', 5, 4)->nullable()->default(0);
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
        Schema::dropIfExists('game_classes');
    }
}
