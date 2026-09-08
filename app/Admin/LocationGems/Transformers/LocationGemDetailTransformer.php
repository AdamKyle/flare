<?php

namespace App\Admin\LocationGems\Transformers;

use App\Admin\Transformers\AdminGemRollTransformer;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Gem;

class LocationGemDetailTransformer
{
    public function __construct(
        private readonly AdminGemRollTransformer $adminGemRollTransformer,
    ) {}

    /**
     * Transform a Location Gem profile into its Admin detail representation.
     */
    public function transform(GameLocationGemParamter $gameLocationGemParamter): array
    {
        return [
            'id' => $gameLocationGemParamter->id,
            'name' => $gameLocationGemParamter->name,
            'description' => $gameLocationGemParamter->description,
            'game_map' => [
                'id' => $gameLocationGemParamter->location->map->id,
                'name' => $gameLocationGemParamter->location->map->name,
            ],
            'location' => [
                'id' => $gameLocationGemParamter->location->id,
                'name' => $gameLocationGemParamter->location->name,
            ],
            'ranges' => $this->transformRanges($gameLocationGemParamter),
            'crafting_skills' => $this->transformCraftingSkills($gameLocationGemParamter),
            'monster_atonement' => $gameLocationGemParamter->monster_atonement,
            'monster_atonement_range' => $gameLocationGemParamter->monster_atonement_range,
            'roll_count' => $gameLocationGemParamter->roll_count,
            'rolled_gem' => $this->transformRolledGem($gameLocationGemParamter),
            'roll_history' => $this->transformRollHistory($gameLocationGemParamter),
            'generated_gem_world' => $this->transformGeneratedGemWorld($gameLocationGemParamter->generatedMap),
        ];
    }

    /**
     * Build the configured range values section, excluding Character Power Reduction.
     */
    private function transformRanges(GameLocationGemParamter $gameLocationGemParamter): array
    {
        $rangeFields = [
            'character_xp_bonus_range', 'character_class_rank_xp_bonus_range', 'kingdom_passive_training_reduction_range',
            'character_class_specialty_xp_gain_range', 'crafting_skill_bonus_range',
            'gold_gain_range', 'gold_dust_gain_range', 'shards_gain_range', 'copper_coin_gain_range',
            'item_drop_chance_increase_range', 'unique_item_drop_chance_increase_range', 'mythic_item_drop_chance_increase_range',
            'cosmic_item_drop_chance_increase_range',
            'enemy_strength_increase_range', 'enemy_healing_increase_range',
            'enemy_spell_evasion_range', 'enemy_affix_resistance_range', 'enemy_entrancing_chance_range',
            'enemy_devouring_light_chance_range', 'enemy_devouring_darkness_chance_range', 'enemy_ambush_chance_range',
            'enemy_ambush_resistance_range', 'enemy_counter_chance_range', 'enemy_counter_resistance_range',
            'enemy_quest_item_drop_chance_increase_range', 'monster_xp_increase_range', 'monster_gold_drop_increase_range',
        ];

        $ranges = [];

        foreach ($rangeFields as $rangeField) {
            $ranges[$rangeField] = $gameLocationGemParamter->{$rangeField};
        }

        return $ranges;
    }

    /**
     * Transform the configured crafting Skills into their compact identity representation.
     */
    private function transformCraftingSkills(GameLocationGemParamter $gameLocationGemParamter): array
    {
        return GameSkill::whereIn('id', $gameLocationGemParamter->crafting_skill_ids ?? [])
            ->orderBy('name')
            ->get()
            ->map(fn (GameSkill $gameSkill): array => ['id' => $gameSkill->id, 'name' => $gameSkill->name])
            ->values()
            ->all();
    }

    /**
     * Transform the profile's currently active rolled Gem into its Admin roll representation.
     */
    private function transformRolledGem(GameLocationGemParamter $gameLocationGemParamter): ?array
    {
        if (is_null($gameLocationGemParamter->rolledGem)) {
            return null;
        }

        return $this->adminGemRollTransformer->transform($gameLocationGemParamter->rolledGem, true);
    }

    /**
     * Transform every Gem roll ever created for this profile, newest first.
     */
    private function transformRollHistory(GameLocationGemParamter $gameLocationGemParamter): array
    {
        return $gameLocationGemParamter->gemRolls()
            ->orderByDesc('roll_number')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Gem $gem): array => $this->adminGemRollTransformer->transform(
                $gem,
                $gem->id === $gameLocationGemParamter->rolled_gem_id,
            ))
            ->values()
            ->all();
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
