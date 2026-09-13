<?php

namespace App\Game\Monsters\Values;

/**
 * The closed set of Monster cache keys built by BuildMonsterCacheService.
 * The canonical per-Game-Map Monster dataset is partitioned under its own
 * key via forGameMap() so a single fight never reads every Map's dataset.
 */
enum MonsterCacheKey: string
{
    case MONSTERS = 'monsters';
    case LOCATION_MONSTERS = 'location-monsters';
    case WEEKLY_MONSTERS = 'weekly-monsters';
    case RAID_MONSTERS = 'raid-monsters';
    case CELESTIALS = 'celestials';

    /**
     * Build the canonical per-Game-Map Monster cache key.
     *
     * @param int $gameMapId
     * @return string
     */
    public static function forGameMap(int $gameMapId): string
    {
        return self::MONSTERS->value.':'.$gameMapId;
    }
}
