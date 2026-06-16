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
            $table->text('description')->nullable();
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
