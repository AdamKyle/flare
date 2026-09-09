<?php

namespace App\Admin\MapGems\Transformers;

use App\Admin\Transformers\AdminGemRollTransformer;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\GameSkill;

class MapGemDetailTransformer
{
    public function __construct(
        private readonly AdminGemRollTransformer $adminGemRollTransformer,
    ) {}

    /**
     * Transform a Map Gem profile into its Admin detail representation.
     */
    public function transform(GameMapGemParamter $gameMapGemParamter): array
    {
        return [
            'id' => $gameMapGemParamter->id,
            'name' => $gameMapGemParamter->name,
            'description' => $gameMapGemParamter->description,
            'game_map' => [
                'id' => $gameMapGemParamter->gameMap->id,
                'name' => $gameMapGemParamter->gameMap->name,
            ],
            'ranges' => $this->transformRanges($gameMapGemParamter),
            'crafting_skills' => $this->transformCraftingSkills($gameMapGemParamter),
            'monster_atonement' => $gameMapGemParamter->monster_atonement,
            'monster_atonement_range' => $gameMapGemParamter->monster_atonement_range,
            'roll_count' => $gameMapGemParamter->roll_count,
            'rolled_gem' => $this->transformRolledGem($gameMapGemParamter),
            'generated_gem_world' => $this->transformGeneratedGemWorld($gameMapGemParamter->generatedMap),
        ];
    }

    /**
     * Build the configured range values section.
     */
    private function transformRanges(GameMapGemParamter $gameMapGemParamter): array
    {
        $rangeFields = [
            'character_xp_bonus_range', 'character_class_rank_xp_bonus_range', 'kingdom_passive_training_reduction_range',
            'character_class_specialty_xp_gain_range', 'crafting_skill_bonus_range',
            'gold_gain_range', 'gold_dust_gain_range', 'shards_gain_range', 'copper_coin_gain_range',
            'item_drop_chance_increase_range', 'unique_item_drop_chance_increase_range', 'mythic_item_drop_chance_increase_range',
            'cosmic_item_drop_chance_increase_range',
            'character_power_reduction_range', 'enemy_strength_increase_range', 'enemy_healing_increase_range',
            'enemy_spell_evasion_range', 'enemy_affix_resistance_range', 'enemy_entrancing_chance_range',
            'enemy_devouring_light_chance_range', 'enemy_devouring_darkness_chance_range', 'enemy_ambush_chance_range',
            'enemy_ambush_resistance_range', 'enemy_counter_chance_range', 'enemy_counter_resistance_range',
            'enemy_quest_item_drop_chance_increase_range', 'monster_xp_increase_range', 'monster_gold_drop_increase_range',
        ];

        $ranges = [];

        foreach ($rangeFields as $rangeField) {
            $ranges[$rangeField] = $gameMapGemParamter->{$rangeField};
        }

        return $ranges;
    }

    /**
     * Transform the configured crafting Skills into their compact identity representation.
     */
    private function transformCraftingSkills(GameMapGemParamter $gameMapGemParamter): array
    {
        return GameSkill::whereIn('id', $gameMapGemParamter->crafting_skill_ids ?? [])
            ->orderBy('name')
            ->get()
            ->map(fn (GameSkill $gameSkill): array => ['id' => $gameSkill->id, 'name' => $gameSkill->name])
            ->values()
            ->all();
    }

    /**
     * Transform the profile's currently active rolled Gem into its Admin roll representation.
     */
    private function transformRolledGem(GameMapGemParamter $gameMapGemParamter): ?array
    {
        if (is_null($gameMapGemParamter->rolledGem)) {
            return null;
        }

        return $this->adminGemRollTransformer->transform($gameMapGemParamter->rolledGem, true);
    }

    /**
     * Transform the generated Gem World Map associated with this profile, when one exists.
     */
    private function transformGeneratedGemWorld(?GameMap $generatedMap): ?array
    {
        if (is_null($generatedMap)) {
            return null;
        }

        return [
            'id' => $generatedMap->id,
            'name' => $generatedMap->name,
            'generated_map_type' => $generatedMap->generated_map_type,
            'parent_map' => is_null($generatedMap->generatedParentMap) ? null : [
                'id' => $generatedMap->generatedParentMap->id,
                'name' => $generatedMap->generatedParentMap->name,
            ],
        ];
    }
}
