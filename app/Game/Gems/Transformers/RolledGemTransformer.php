<?php

namespace App\Game\Gems\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Gem;

/**
 * Shared permission-neutral factual transformation for a single rolled Gem,
 * reused by Admin and Player Gem presentation.
 */
class RolledGemTransformer
{
    /**
     * Transform a Gem roll into its factual HTTP representation.
     */
    public function transform(Gem $gem, bool $isActive): array
    {
        return [
            'id' => $gem->id,
            'name' => $gem->name,
            'domain' => $gem->domain,
            'roll_number' => $gem->roll_number,
            'is_active' => $isActive,
            'crafting_skills' => $this->transformCraftingSkills($gem),
            'monster_atonement' => $gem->monster_atonement,
            'monster_atonement_amount' => $gem->monster_atonement_amount,
            'character_xp_bonus' => $gem->character_xp_bonus,
            'character_class_rank_xp_bonus' => $gem->character_class_rank_xp_bonus,
            'kingdom_passive_training_reduction' => $gem->kingdom_passive_training_reduction,
            'gold_gain' => $gem->gold_gain,
            'gold_dust_gain' => $gem->gold_dust_gain,
            'shards_gain' => $gem->shards_gain,
            'copper_coin_gain' => $gem->copper_coin_gain,
            'character_class_specialty_xp_gain' => $gem->character_class_specialty_xp_gain,
            'crafting_skill_bonus' => $gem->crafting_skill_bonus,
            'item_drop_chance_increase' => $gem->item_drop_chance_increase,
            'unique_item_drop_chance_increase' => $gem->unique_item_drop_chance_increase,
            'mythic_item_drop_chance_increase' => $gem->mythic_item_drop_chance_increase,
            'cosmic_item_drop_chance_increase' => $gem->cosmic_item_drop_chance_increase,
            'character_power_reduction' => $gem->character_power_reduction,
            'enemy_strength_increase' => $gem->enemy_strength_increase,
            'enemy_healing_increase' => $gem->enemy_healing_increase,
            'enemy_spell_evasion' => $gem->enemy_spell_evasion,
            'enemy_affix_resistance' => $gem->enemy_affix_resistance,
            'enemy_entrancing_chance' => $gem->enemy_entrancing_chance,
            'enemy_devouring_light_chance' => $gem->enemy_devouring_light_chance,
            'enemy_devouring_darkness_chance' => $gem->enemy_devouring_darkness_chance,
            'enemy_ambush_chance' => $gem->enemy_ambush_chance,
            'enemy_ambush_resistance' => $gem->enemy_ambush_resistance,
            'enemy_counter_chance' => $gem->enemy_counter_chance,
            'enemy_counter_resistance' => $gem->enemy_counter_resistance,
            'enemy_quest_item_drop_chance_increase' => $gem->enemy_quest_item_drop_chance_increase,
            'monster_xp_increase' => $gem->monster_xp_increase,
            'monster_gold_drop_increase' => $gem->monster_gold_drop_increase,
        ];
    }

    /**
     * Transform the Gem roll's crafting Skills into their compact identity representation.
     */
    private function transformCraftingSkills(Gem $gem): array
    {
        return GameSkill::whereIn('id', $gem->crafting_skill_ids ?? [])
            ->orderBy('name')
            ->get()
            ->map(fn (GameSkill $gameSkill): array => ['id' => $gameSkill->id, 'name' => $gameSkill->name])
            ->values()
            ->all();
    }
}
