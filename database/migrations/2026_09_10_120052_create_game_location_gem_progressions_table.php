<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the shared global Gem progression track for each Location Gem profile.
     */
    public function up(): void
    {
        Schema::create('game_location_gem_progressions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('game_location_gem_paramter_id');
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('xp')->default(0);
            $table->timestamps();

            $table->unique('game_location_gem_paramter_id', 'game_location_gem_progressions_unique');

            $table->foreign('game_location_gem_paramter_id', 'game_location_gem_progressions_paramter_foreign')
                ->references('id')
                ->on('game_location_gem_paramters')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_location_gem_progressions');
    }
};
