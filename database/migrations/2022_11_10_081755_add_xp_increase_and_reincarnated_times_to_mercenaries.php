<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('character_mercenaries', function (Blueprint $table) {
            $table->decimal('xp_increase', 12,4)->nullable();
            $table->integer('times_reincarnated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('character_mercenaries', function (Blueprint $table) {
            $table->dropColumn('xp_increase');
            $table->dropColumn('times_reincarnated');
        });
    }
};
