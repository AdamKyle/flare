<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            $table->dropColumn('fight_time_out_mod_bonus');
            $table->dropColumn('move_time_out_mod_bonus');
            $table->dropColumn('class_bonus');
            $table->dropColumn('base_damage_mod_bonus');
            $table->dropColumn('base_healing_mod_bonus');
            $table->dropColumn('base_ac_mod_bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_affixes', function (Blueprint $table) {
            //
        });
    }
};
