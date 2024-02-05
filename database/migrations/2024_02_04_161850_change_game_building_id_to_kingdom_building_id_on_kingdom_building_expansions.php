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
        Schema::table('kingdom_building_expansions', function (Blueprint $table) {
            $table->renameColumn('game_building_id', 'kingdom_building_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdom_building_expansions', function (Blueprint $table) {
            //
        });
    }
};
