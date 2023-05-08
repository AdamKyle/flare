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
        Schema::table('raid_participations', function (Blueprint $table) {
            $table->unsignedBigInteger('raid_boss_id')->after('character_id');
            $table->foreign('raid_boss_id')->references('id')->on('raid_bosses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raid_participations', function (Blueprint $table) {
            //
        });
    }
};
