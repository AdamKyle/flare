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
    case SPECIAL_LOCATION = 'special_location';
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
     * The fixed set of Location Types that make up the Weekly Fight category.
     * Cave of Memories is intentionally excluded and remains under Special Location.
     *
     * @return array<int, int> Valid LocationType values for the Weekly Fight category.
     */
    public static function weeklyFightLocationTypes(): array
    {
        return [
            LocationType::ALCHEMY_CHURCH->value,
            LocationType::LORDS_STRONG_HOLD->value,
            LocationType::BROKEN_ANVIL->value,
            LocationType::TWISTED_MAIDENS_DUNGEONS->value,
        ];
    }
}
