<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMaxvalueOfHealthInCharacterInCelestialFights extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('character_in_celestial_fights', function (Blueprint $table) {
            $table->bigInteger('character_max_health')->change();
            $table->bigInteger('character_current_health')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('character_in_celestial_fights', function (Blueprint $table) {
            $table->integer('character_max_health')->change();
            $table->integer('character_current_health')->change();
        });
    }
}
