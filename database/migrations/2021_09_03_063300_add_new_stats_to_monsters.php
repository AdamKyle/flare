<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewStatsToMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->integer('agi')->after('int');
            $table->integer('focus')->after('agi');
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
            $table->dropColumn('agi');
            $table->dropColumn('focus');
        });
    }
}
