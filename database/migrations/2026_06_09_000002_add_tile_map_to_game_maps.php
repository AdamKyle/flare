<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            if (! Schema::hasColumn('game_maps', 'tile_map')) {
                $table->json('tile_map')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_maps', function (Blueprint $table) {
            if (Schema::hasColumn('game_maps', 'tile_map')) {
                $table->dropColumn('tile_map');
            }
        });
    }
};
