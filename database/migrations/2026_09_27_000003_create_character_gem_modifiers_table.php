<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the three rolled modifier rows that belong to each character-domain Gem.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('character_gem_modifiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('gem_id');
            $table->unsignedTinyInteger('roll_position');
            $table->string('modifier_type');
            $table->decimal('amount', 12, 8)->nullable();
            $table->unsignedBigInteger('game_gem_ability_id')->nullable();
            $table->timestamps();

            $table->index('gem_id');
            $table->index('modifier_type');
            $table->index('game_gem_ability_id');
            $table->unique(['gem_id', 'roll_position']);

            $table->foreign('gem_id')
                ->references('id')
                ->on('gems')
                ->cascadeOnDelete();

            $table->foreign('game_gem_ability_id')
                ->references('id')
                ->on('game_gem_abilities');
        });
    }

    /**
     * Drop the character Gem modifier rows table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('character_gem_modifiers');
    }
};
