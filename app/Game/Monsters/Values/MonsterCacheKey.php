<?php

namespace App\Game\Monsters\Values;

/**
 * The closed set of Monster cache keys built by BuildMonsterCacheService.
 */
enum MonsterCacheKey: string
{
    case MONSTERS = 'monsters';
    case LOCATION_MONSTERS = 'location-monsters';
    case WEEKLY_MONSTERS = 'weekly-monsters';
    case RAID_MONSTERS = 'raid-monsters';
    case CELESTIALS = 'celestials';
}
