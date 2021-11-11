<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCurrentHealthColumnTypeOnCelestialFights extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('celestial_fights', function (Blueprint $table) {
            $table->bigInteger('current_health')->change();
            $table->bigInteger('max_health')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->integer('current_health')->change();
        $table->integer('max_health')->change();
    }
}
