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
            $table->string('gem_world_name')->nullable()->unique()->after('name');
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->string('gem_world_name')->nullable()->unique()->after('name');
        });

        Schema::table('game_maps', function (Blueprint $table) {
            $table->string('generated_asset_name')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            $table->dropColumn('generated_asset_name');
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->dropColumn('gem_world_name');
        });

        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->dropColumn('gem_world_name');
        });
    }
};
