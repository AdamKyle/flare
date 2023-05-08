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
        Schema::table('raid_bosses', function (Blueprint $table) {
            $table->bigInteger('boss_max_hp')->nullable()->change();
            $table->bigInteger('boss_current_hp')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raid_bosses', function (Blueprint $table) {
            //
        });
    }
};
