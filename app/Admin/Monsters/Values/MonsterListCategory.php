<?php

namespace App\Admin\Monsters\Values;

use App\Game\Maps\Values\LocationType;

/**
 * Admin Monster list/query filter value. Not persisted; never added as a
 * database column.
 */
enum MonsterListCategory: string
{
    case ALL = 'all';
    case REGULAR = 'regular';
    case RAID_MONSTER = 'raid_monster';
    case RAID_BOSS = 'raid_boss';
    case CELESTIAL = 'celestial';
    case WEEKLY_FIGHT = 'weekly_fight';

    /**
     * Values.
     */
    public static function values(): array
    {
        return array_map(
            fn (MonsterListCategory $category): string => $category->value,
            self::cases()
        );
    }

    /**
     * Return the Location Types included in the Weekly Fight category.
     */
    public static function weeklyFightLocationTypes(): array
    {
        return LocationType::weeklyFightLocationTypes();
    }

    /**
     * Return dedicated Location Types included only in the All category.
     */
    public static function allCategoryLocationTypes(): array
    {
        return array_merge(LocationType::weeklyFightLocationTypes(), [LocationType::CAVE_OF_MEMORIES->value]);
    }
}
