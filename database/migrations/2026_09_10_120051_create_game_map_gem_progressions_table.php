<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the shared global Gem progression track for each Map Gem profile.
     */
    public function up(): void
    {
        Schema::create('game_map_gem_progressions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('game_map_gem_paramter_id')->unique();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('xp')->default(0);
            $table->timestamps();

            $table->foreign('game_map_gem_paramter_id')
                ->references('id')
                ->on('game_map_gem_paramters')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_map_gem_progressions');
    }
};
