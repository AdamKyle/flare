<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->unsignedBigInteger('rolled_gem_id')->nullable()->index();
            $table->unsignedInteger('roll_count')->default(0);

            $table->foreign('rolled_gem_id')
                ->references('id')
                ->on('gems')
                ->nullOnDelete();
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->unsignedBigInteger('rolled_gem_id')->nullable()->index();
            $table->unsignedInteger('roll_count')->default(0);

            $table->foreign('rolled_gem_id')
                ->references('id')
                ->on('gems')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_map_gem_paramters', function (Blueprint $table) {
            $table->dropForeign(['rolled_gem_id']);
            $table->dropColumn(['rolled_gem_id', 'roll_count']);
        });

        Schema::table('game_location_gem_paramters', function (Blueprint $table) {
            $table->dropForeign(['rolled_gem_id']);
            $table->dropColumn(['rolled_gem_id', 'roll_count']);
        });
    }
};
