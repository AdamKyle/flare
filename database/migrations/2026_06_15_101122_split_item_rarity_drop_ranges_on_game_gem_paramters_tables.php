<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->string('unique_item_drop_chance_increase_range')->nullable();
            $table->string('mythic_item_drop_chance_increase_range')->nullable();
            $table->string('cosmic_item_drop_chance_increase_range')->nullable();
            $table->string('ascended_item_drop_chance_increase_range')->nullable();
            $table->string('character_power_reduction_range')->nullable();
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->string('unique_item_drop_chance_increase_range')->nullable();
            $table->string('mythic_item_drop_chance_increase_range')->nullable();
            $table->string('cosmic_item_drop_chance_increase_range')->nullable();
            $table->string('ascended_item_drop_chance_increase_range')->nullable();
        });

        if (Schema::hasColumn('game_map_gem_paramters', 'unique_mythic_cosmic_item_drop_chance_increase_range')) {
            DB::table('game_map_gem_paramters')->update([
                'unique_item_drop_chance_increase_range' => DB::raw('unique_mythic_cosmic_item_drop_chance_increase_range'),
                'mythic_item_drop_chance_increase_range' => DB::raw('unique_mythic_cosmic_item_drop_chance_increase_range'),
                'cosmic_item_drop_chance_increase_range' => DB::raw('unique_mythic_cosmic_item_drop_chance_increase_range'),
            ]);

            Schema::table('game_map_gem_paramters', function (Blueprint $table) {
                $table->dropColumn('unique_mythic_cosmic_item_drop_chance_increase_range');
            });
        }

        if (Schema::hasColumn('game_location_gem_paramters', 'unique_mythic_cosmic_item_drop_chance_increase_range')) {
            DB::table('game_location_gem_paramters')->update([
                'unique_item_drop_chance_increase_range' => DB::raw('unique_mythic_cosmic_item_drop_chance_increase_range'),
                'mythic_item_drop_chance_increase_range' => DB::raw('unique_mythic_cosmic_item_drop_chance_increase_range'),
                'cosmic_item_drop_chance_increase_range' => DB::raw('unique_mythic_cosmic_item_drop_chance_increase_range'),
            ]);

            Schema::table('game_location_gem_paramters', function (Blueprint $table) {
                $table->dropColumn('unique_mythic_cosmic_item_drop_chance_increase_range');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->string('unique_mythic_cosmic_item_drop_chance_increase_range')->nullable();
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->string('unique_mythic_cosmic_item_drop_chance_increase_range')->nullable();
        });

        DB::table('game_map_gem_paramters')->update([
            'unique_mythic_cosmic_item_drop_chance_increase_range' => DB::raw('unique_item_drop_chance_increase_range'),
        ]);

        DB::table('game_location_gem_paramters')->update([
            'unique_mythic_cosmic_item_drop_chance_increase_range' => DB::raw('unique_item_drop_chance_increase_range'),
        ]);

        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->dropColumn([
                'unique_item_drop_chance_increase_range',
                'mythic_item_drop_chance_increase_range',
                'cosmic_item_drop_chance_increase_range',
                'ascended_item_drop_chance_increase_range',
                'character_power_reduction_range',
            ]);
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->dropColumn([
                'unique_item_drop_chance_increase_range',
                'mythic_item_drop_chance_increase_range',
                'cosmic_item_drop_chance_increase_range',
                'ascended_item_drop_chance_increase_range',
            ]);
        });
    }
};
