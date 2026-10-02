<?php

namespace Tests\Traits;

trait CreateMonsterImportRow
{
    /**
     * Build a minimal, otherwise-valid Monsters import row, keyed by header name.
     *
     * @param array $overrides
     * @return array
     */
    public function minimalMonsterImportRow(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Import Monster',
            'damage_stat' => 'str',
            'game_map_id' => 'Import Map',
            'max_level' => 5,
            'xp' => 10,
            'gold' => 10,
            'health_range' => '1-8',
            'attack_range' => '1-6',
            'drop_check' => 5,
            'str' => 1,
            'dur' => 1,
            'dex' => 1,
            'chr' => 1,
            'int' => 1,
            'agi' => 1,
            'focus' => 1,
            'ac' => 1,
            'can_cast' => 'false',
            'is_celestial_entity' => 'false',
            'is_raid_monster' => 'false',
            'is_raid_boss' => 'false',
            'fire_penetration' => 0,
            'ice_penetration' => 0,
            'water_penetration' => 0,
        ], $overrides);
    }
}
