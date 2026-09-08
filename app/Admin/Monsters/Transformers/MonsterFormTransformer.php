<?php

namespace App\Admin\Monsters\Transformers;

use App\Flare\Models\Monster;

class MonsterFormTransformer
{
    /**
     * Transform a Monster into its Admin save-response / form-value representation.
     */
    public function transform(Monster $monster): array
    {
        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'damage_stat' => $monster->damage_stat,
            'game_map_id' => $monster->game_map_id,
            'max_level' => $monster->max_level,
            'xp' => $monster->xp,
            'gold' => $monster->gold,
            'health_range' => $monster->health_range,
            'attack_range' => $monster->attack_range,
            'drop_check' => $monster->drop_check,
            'only_for_location_type' => $monster->only_for_location_type,

            'str' => $monster->str,
            'dur' => $monster->dur,
            'dex' => $monster->dex,
            'chr' => $monster->chr,
            'int' => $monster->int,
            'agi' => $monster->agi,
            'focus' => $monster->focus,
            'ac' => $monster->ac,

            'accuracy' => $monster->accuracy,
            'dodge' => $monster->dodge,
            'criticality' => $monster->criticality,
            'ambush_chance' => $monster->ambush_chance,
            'ambush_resistance' => $monster->ambush_resistance,
            'counter_chance' => $monster->counter_chance,
            'counter_resistance' => $monster->counter_resistance,

            'can_cast' => $monster->can_cast,
            'max_spell_damage' => $monster->max_spell_damage,
            'casting_accuracy' => $monster->casting_accuracy,
            'spell_evasion' => $monster->spell_evasion,
            'max_affix_damage' => $monster->max_affix_damage,
            'affix_resistance' => $monster->affix_resistance,
            'healing_percentage' => $monster->healing_percentage,
            'entrancing_chance' => $monster->entrancing_chance,
            'devouring_light_chance' => $monster->devouring_light_chance,
            'devouring_darkness_chance' => $monster->devouring_darkness_chance,
            'life_stealing_resistance' => $monster->life_stealing_resistance,

            'quest_item_id' => $monster->quest_item_id,
            'quest_item_drop_chance' => $monster->quest_item_drop_chance,
            'is_celestial_entity' => $monster->is_celestial_entity,
            'celestial_type' => $monster->celestial_type,
            'gold_cost' => $monster->gold_cost,
            'gold_dust_cost' => $monster->gold_dust_cost,
            'shards' => $monster->shards,

            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
            'raid_special_attack_type' => $monster->raid_special_attack_type,
            'fire_atonement' => $monster->fire_atonement,
            'ice_atonement' => $monster->ice_atonement,
            'water_atonement' => $monster->water_atonement,
        ];
    }
}
