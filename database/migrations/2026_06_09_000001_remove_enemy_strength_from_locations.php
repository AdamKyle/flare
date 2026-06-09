<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'enemy_strength_increase')) {
                $table->dropColumn('enemy_strength_increase');
            }

            if (Schema::hasColumn('locations', 'enemy_strength_type')) {
                $table->dropColumn('enemy_strength_type');
            }

            if (Schema::hasColumn('locations', 'delve_enemy_strength_increase')) {
                $table->dropColumn('delve_enemy_strength_increase');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'enemy_strength_increase')) {
                $table->float('enemy_strength_increase', 8, 4)->nullable();
            }

            if (! Schema::hasColumn('locations', 'enemy_strength_type')) {
                $table->integer('enemy_strength_type')->nullable();
            }

            if (! Schema::hasColumn('locations', 'delve_enemy_strength_increase')) {
                $table->decimal('delve_enemy_strength_increase', 8, 4)->nullable();
            }
        });
    }
};
