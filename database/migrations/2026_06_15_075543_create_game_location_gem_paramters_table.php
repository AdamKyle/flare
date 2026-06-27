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
        Schema::create('game_location_gem_paramters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->unique();
            $table->string('name')->index();
            $table->string('character_xp_bonus_range')->nullable();
            $table->string('character_class_rank_xp_bonus_range')->nullable();
            $table->string('kingdom_passive_training_reduction_range')->nullable();
            $table->string('gold_gain_range')->nullable();
            $table->string('gold_dust_gain_range')->nullable();
            $table->string('shards_gain_range')->nullable();
            $table->string('copper_coin_gain_range')->nullable();
            $table->string('character_class_specialty_xp_gain_range')->nullable();
            $table->json('crafting_skill_ids')->nullable();
            $table->string('crafting_skill_bonus_range')->nullable();
            $table->string('item_drop_chance_increase_range')->nullable();
            $table->string('unique_mythic_cosmic_item_drop_chance_increase_range')->nullable();
            $table->string('enemy_strength_increase_range')->nullable();
            $table->string('enemy_healing_increase_range')->nullable();
            $table->string('enemy_spell_evasion_range')->nullable();
            $table->string('enemy_affix_resistance_range')->nullable();
            $table->string('enemy_entrancing_chance_range')->nullable();
            $table->string('enemy_devouring_light_chance_range')->nullable();
            $table->string('enemy_devouring_darkness_chance_range')->nullable();
            $table->string('enemy_ambush_chance_range')->nullable();
            $table->string('enemy_ambush_resistance_range')->nullable();
            $table->string('enemy_counter_chance_range')->nullable();
            $table->string('enemy_counter_resistance_range')->nullable();
            $table->string('enemy_quest_item_drop_chance_increase_range')->nullable();
            $table->string('monster_xp_increase_range')->nullable();
            $table->string('monster_gold_drop_increase_range')->nullable();
            $table->string('faction_point_increase_range')->nullable();
            $table->integer('monster_atonement')->nullable();
            $table->string('monster_atonement_range')->nullable();
            $table->timestamps();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_location_gem_paramters');
    }
};
