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
            $table->decimal('str_mod', 50, 8)->change();
            $table->decimal('dex_mod', 50, 8)->change();
            $table->decimal('dur_mod', 50, 8)->change();
            $table->decimal('int_mod', 50, 8)->change();
            $table->decimal('chr_mod', 50, 8)->change();
            $table->decimal('agi_mod', 50, 8)->change();
            $table->decimal('focus_mod', 50, 8)->change();
            $table->decimal('base_damage_mod', 50, 8)->change();
            $table->decimal('base_ac_mod', 50, 8)->change();
            $table->decimal('base_healing_mod', 50, 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
