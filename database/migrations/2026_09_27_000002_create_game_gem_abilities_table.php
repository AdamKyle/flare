<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the Admin-authored Gem Ability definitions rolled onto Tier 1 character Gems.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('game_gem_abilities', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->string('ability_type');
            $table->string('effect_type');
            $table->json('attack_types');
            $table->decimal('proc_chance', 12, 8)->nullable();
            $table->decimal('effect_value', 12, 8);
            $table->string('scaling_source')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Drop the Gem Ability definitions table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('game_gem_abilities');
    }
};
