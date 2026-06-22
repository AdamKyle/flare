<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gems', function (Blueprint $table) {
            $table->string('domain')->default('character')->index();
            $table->unsignedBigInteger('rolled_by_user_id')->nullable()->index();
            $table->unsignedInteger('roll_number')->default(1);
            $table->unsignedBigInteger('game_map_gem_paramters_id')->nullable()->index();
            $table->unsignedBigInteger('game_location_gem_paramters_id')->nullable()->index();
            $table->decimal('character_xp_bonus', 12, 8)->nullable();
            $table->decimal('character_class_rank_xp_bonus', 12, 8)->nullable();
            $table->decimal('kingdom_passive_training_reduction', 12, 8)->nullable();
            $table->decimal('gold_gain', 12, 8)->nullable();
            $table->decimal('gold_dust_gain', 12, 8)->nullable();
            $table->decimal('shards_gain', 12, 8)->nullable();
            $table->decimal('copper_coin_gain', 12, 8)->nullable();
            $table->decimal('character_class_specialty_xp_gain', 12, 8)->nullable();
            $table->json('crafting_skill_ids')->nullable();
            $table->decimal('crafting_skill_bonus', 12, 8)->nullable();
            $table->decimal('item_drop_chance_increase', 12, 8)->nullable();
            $table->decimal('unique_item_drop_chance_increase', 12, 8)->nullable();
            $table->decimal('mythic_item_drop_chance_increase', 12, 8)->nullable();
            $table->decimal('cosmic_item_drop_chance_increase', 12, 8)->nullable();
            $table->decimal('ascended_item_drop_chance_increase', 12, 8)->nullable();
            $table->decimal('character_power_reduction', 12, 8)->nullable();
            $table->decimal('enemy_strength_increase', 12, 8)->nullable();
            $table->decimal('enemy_healing_increase', 12, 8)->nullable();
            $table->decimal('enemy_spell_evasion', 12, 8)->nullable();
            $table->decimal('enemy_affix_resistance', 12, 8)->nullable();
            $table->decimal('enemy_entrancing_chance', 12, 8)->nullable();
            $table->decimal('enemy_devouring_light_chance', 12, 8)->nullable();
            $table->decimal('enemy_devouring_darkness_chance', 12, 8)->nullable();
            $table->decimal('enemy_ambush_chance', 12, 8)->nullable();
            $table->decimal('enemy_ambush_resistance', 12, 8)->nullable();
            $table->decimal('enemy_counter_chance', 12, 8)->nullable();
            $table->decimal('enemy_counter_resistance', 12, 8)->nullable();
            $table->decimal('enemy_quest_item_drop_chance_increase', 12, 8)->nullable();
            $table->decimal('monster_xp_increase', 12, 8)->nullable();
            $table->decimal('monster_gold_drop_increase', 12, 8)->nullable();
            $table->decimal('faction_point_increase', 12, 8)->nullable();
            $table->integer('monster_atonement')->nullable();
            $table->decimal('monster_atonement_amount', 12, 8)->nullable();

            $table->integer('tier')->nullable()->change();
            $table->integer('primary_atonement_type')->nullable()->change();
            $table->integer('secondary_atonement_type')->nullable()->change();
            $table->integer('tertiary_atonement_type')->nullable()->change();
            $table->decimal('primary_atonement_amount', 12, 8)->nullable()->change();
            $table->decimal('secondary_atonement_amount', 12, 8)->nullable()->change();
            $table->decimal('tertiary_atonement_amount', 12, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('gems')->where('domain', '<>', 'character')->delete();

        Schema::table('gems', function (Blueprint $table) {
            $table->dropColumn([
                'domain',
                'rolled_by_user_id',
                'roll_number',
                'game_map_gem_paramters_id',
                'game_location_gem_paramters_id',
                'character_xp_bonus',
                'character_class_rank_xp_bonus',
                'kingdom_passive_training_reduction',
                'gold_gain',
                'gold_dust_gain',
                'shards_gain',
                'copper_coin_gain',
                'character_class_specialty_xp_gain',
                'crafting_skill_ids',
                'crafting_skill_bonus',
                'item_drop_chance_increase',
                'unique_item_drop_chance_increase',
                'mythic_item_drop_chance_increase',
                'cosmic_item_drop_chance_increase',
                'ascended_item_drop_chance_increase',
                'character_power_reduction',
                'enemy_strength_increase',
                'enemy_healing_increase',
                'enemy_spell_evasion',
                'enemy_affix_resistance',
                'enemy_entrancing_chance',
                'enemy_devouring_light_chance',
                'enemy_devouring_darkness_chance',
                'enemy_ambush_chance',
                'enemy_ambush_resistance',
                'enemy_counter_chance',
                'enemy_counter_resistance',
                'enemy_quest_item_drop_chance_increase',
                'monster_xp_increase',
                'monster_gold_drop_increase',
                'faction_point_increase',
                'monster_atonement',
                'monster_atonement_amount',
            ]);

            $table->integer('tier')->nullable(false)->change();
            $table->integer('primary_atonement_type')->nullable(false)->change();
            $table->integer('secondary_atonement_type')->nullable(false)->change();
            $table->integer('tertiary_atonement_type')->nullable(false)->change();
            $table->decimal('primary_atonement_amount', 12, 8)->nullable(false)->change();
            $table->decimal('secondary_atonement_amount', 12, 8)->nullable(false)->change();
            $table->decimal('tertiary_atonement_amount', 12, 8)->nullable(false)->change();
        });
    }
};
