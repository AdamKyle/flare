<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCelestialInfoToMonsters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->boolean('is_celestial_entity')->default(false);
            $table->integer('gold_cost')->nullable();
            $table->integer('gold_dust_cost')->nullable();
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
            $table->dropColumn('is_celestial_entity');
            $table->dropColumn('gold_cost')->nullable();
            $table->dropColumn('gold_dust_cost')->nullable();
        });
    }
}
