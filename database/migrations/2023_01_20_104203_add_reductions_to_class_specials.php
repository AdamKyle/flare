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
        Schema::table('game_class_specials', function (Blueprint $table) {
            $table->decimal('spell_evasion', 12, 8)->nullable();
            $table->decimal('affix_damage_reduction', 12, 8)->nullable();
            $table->decimal('healing_reduction', 12, 8)->nullable();
            $table->decimal('skill_reduction', 12, 8)->nullable();
            $table->decimal('resistance_reduction', 12, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_class_specials', function (Blueprint $table) {
            $table->dropColumn('spell_evasion');
            $table->dropColumn('affix_damage_reduction');
            $table->dropColumn('healing_reduction');
            $table->dropColumn('skill_reduction');
            $table->dropColumn('resistance_reduction');
        });
    }
};
