<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the active Gem Scroll rows a Character has applied to a Location Gem World.
     */
    public function up(): void
    {
        Schema::create('character_game_location_gem_scrolls', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('game_location_gem_paramter_id');
            $table->unsignedBigInteger('item_id');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['character_id', 'game_location_gem_paramter_id', 'expires_at'], 'char_location_gem_scrolls_lookup_index');

            $table->foreign('character_id')
                ->references('id')
                ->on('characters')
                ->cascadeOnDelete();

            $table->foreign('game_location_gem_paramter_id', 'char_location_gem_scrolls_paramter_foreign')
                ->references('id')
                ->on('game_location_gem_paramters')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_game_location_gem_scrolls');
    }
};
