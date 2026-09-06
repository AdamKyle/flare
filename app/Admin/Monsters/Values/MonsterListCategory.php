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
     * @return array<int, string> All valid category values, for validation rules.
     */
    public static function values(): array
    {
        return array_map(
            fn (MonsterListCategory $category): string => $category->value,
            self::cases()
        );
    }

    /**
     * The authoritative set of Location Types that make up the Weekly Fight category.
     * Cave of Memories is intentionally excluded.
     *
     * @return array<int, int> Valid LocationType values for the Weekly Fight category.
     */
    public static function weeklyFightLocationTypes(): array
    {
        return LocationType::weeklyFightLocationTypes();
    }

    /**
     * The dedicated Location Types that remain factually visible under the "All" category
     * without owning a dedicated category tab: the Weekly Fight set plus the Cave of
     * Memories dedicated Monster population.
     *
     * @return array<int, int> Valid LocationType values visible under the "All" category.
     */
    public static function allCategoryLocationTypes(): array
    {
        return array_merge(LocationType::weeklyFightLocationTypes(), [LocationType::CAVE_OF_MEMORIES->value]);
    }
}
