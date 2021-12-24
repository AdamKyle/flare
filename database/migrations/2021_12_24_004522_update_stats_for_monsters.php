<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatsForMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->bigInteger('str')->change();
            $table->bigInteger('dur')->change();
            $table->bigInteger('dex')->change();
            $table->bigInteger('chr')->change();
            $table->bigInteger('int')->change();
            $table->bigInteger('agi')->change();
            $table->bigInteger('focus')->change();
            $table->bigInteger('ac')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->integer('str')->change();
            $table->integer('dur')->change();
            $table->integer('dex')->change();
            $table->integer('chr')->change();
            $table->integer('int')->change();
            $table->integer('agi')->change();
            $table->integer('focus')->change();
            $table->integer('ac')->change();
        });
    }
}
