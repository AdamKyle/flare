<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModifiersToCharacterBoons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('character_boons', function (Blueprint $table) {
            $table->decimal('base_damage_mod', 8, 4)->nullable();
            $table->decimal('base_healing_mod', 8, 4)->nullable();
            $table->decimal('base_ac_mod', 8, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('character_boons', function (Blueprint $table) {
            $table->dropColumn('base_damage_mod');
            $table->dropColumn('base_healing_mod');
            $table->dropColumn('base_ac_mod');
        });
    }
}
