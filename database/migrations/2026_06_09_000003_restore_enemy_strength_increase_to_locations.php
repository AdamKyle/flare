<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'enemy_strength_increase')) {
                $table->float('enemy_strength_increase', 8, 4)->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'enemy_strength_increase')) {
                $table->dropColumn('enemy_strength_increase');
            }
        });
    }
};
