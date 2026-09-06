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
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->dropColumn(['faction_point_increase_range', 'ascended_item_drop_chance_increase_range']);
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->dropColumn(['faction_point_increase_range', 'ascended_item_drop_chance_increase_range']);
        });

        Schema::table('gems', function (Blueprint $table) {
            $table->dropColumn(['faction_point_increase', 'ascended_item_drop_chance_increase']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * The dropped columns' values cannot be restored; this recreates the nullable columns only.
     */
    public function down(): void
    {
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->string('faction_point_increase_range')->nullable();
            $table->string('ascended_item_drop_chance_increase_range')->nullable();
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->string('faction_point_increase_range')->nullable();
            $table->string('ascended_item_drop_chance_increase_range')->nullable();
        });

        Schema::table('gems', function (Blueprint $table) {
            $table->decimal('faction_point_increase', 12, 8)->nullable();
            $table->decimal('ascended_item_drop_chance_increase', 12, 8)->nullable();
        });
    }
};
