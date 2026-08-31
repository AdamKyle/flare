<?php

namespace Tests\Traits;

trait CreateMonsterImportRow
{
    /**
     * Build a minimal, otherwise-valid Monsters import row, keyed by header name.
     *
     * @param  array<string, mixed>  $overrides  Values to override on top of the minimal valid row.
     * @return array<string, mixed> Row values keyed by header name.
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
        ], $overrides);
    }
}
