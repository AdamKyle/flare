<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the personal per-Character Gem progression track for each Location Gem profile.
     */
    public function up(): void
    {
        Schema::create('character_game_location_gem_progressions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('game_location_gem_paramter_id');
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('xp')->default(0);
            $table->timestamps();

            $table->unique(['character_id', 'game_location_gem_paramter_id'], 'char_location_gem_progressions_unique');
            $table->index(['character_id', 'level'], 'char_location_gem_progressions_char_level_index');

            $table->foreign('character_id')
                ->references('id')
                ->on('characters')
                ->cascadeOnDelete();

            $table->foreign('game_location_gem_paramter_id', 'char_location_gem_progressions_paramter_foreign')
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
        Schema::dropIfExists('character_game_location_gem_progressions');
    }
};
