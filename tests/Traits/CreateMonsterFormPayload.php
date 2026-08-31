<?php

namespace Tests\Traits;

trait CreateMonsterFormPayload
{
    /**
     * Build a minimal, otherwise-valid Monster Admin form payload.
     *
     * @param  int  $gameMapId  Game Map id to place the Monster on.
     * @param  array<string, mixed>  $overrides  Values to override on top of the minimal valid payload.
     * @return array<string, mixed> Monster Admin form payload.
     */
    public function minimalMonsterFormPayload(int $gameMapId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Minimal Monster',
            'damage_stat' => 'str',
            'game_map_id' => $gameMapId,
            'max_level' => 10,
            'xp' => 100,
            'gold' => 50,
            'health_range' => '1-10',
            'attack_range' => '1-5',
            'drop_check' => 5,
            'only_for_location_type' => null,
            'str' => 1,
            'dur' => 1,
            'dex' => 1,
            'chr' => 1,
            'int' => 1,
            'agi' => 1,
            'focus' => 1,
            'ac' => 1,
            'accuracy' => null,
            'dodge' => null,
            'criticality' => null,
            'ambush_chance' => null,
            'ambush_resistance' => null,
            'counter_chance' => null,
            'counter_resistance' => null,
            'can_cast' => false,
            'max_spell_damage' => null,
            'casting_accuracy' => null,
            'spell_evasion' => null,
            'max_affix_damage' => null,
            'affix_resistance' => null,
            'healing_percentage' => null,
            'entrancing_chance' => null,
            'devouring_light_chance' => null,
            'devouring_darkness_chance' => null,
            'life_stealing_resistance' => null,
            'quest_item_id' => null,
            'quest_item_drop_chance' => null,
            'is_celestial_entity' => false,
            'celestial_type' => null,
            'gold_cost' => null,
            'gold_dust_cost' => null,
            'shards' => null,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'raid_special_attack_type' => null,
            'fire_atonement' => null,
            'ice_atonement' => null,
            'water_atonement' => null,
        ], $overrides);
    }
}
