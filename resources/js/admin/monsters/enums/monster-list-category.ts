import { LocationType } from '../../locations/enums/location-type';

export enum MonsterListCategory {
  ALL = 'all',
  REGULAR = 'regular',
  RAID_MONSTER = 'raid_monster',
  RAID_BOSS = 'raid_boss',
  CELESTIAL = 'celestial',
  SPECIAL_LOCATION = 'special_location',
  WEEKLY_FIGHT = 'weekly_fight',
}

export const MONSTER_LIST_CATEGORY_LABELS: Record<MonsterListCategory, string> =
  {
    [MonsterListCategory.ALL]: 'All Monsters',
    [MonsterListCategory.REGULAR]: 'Regular',
    [MonsterListCategory.RAID_MONSTER]: 'Raid Monsters',
    [MonsterListCategory.RAID_BOSS]: 'Raid Bosses',
    [MonsterListCategory.CELESTIAL]: 'Celestials',
    [MonsterListCategory.SPECIAL_LOCATION]: 'Special Location',
    [MonsterListCategory.WEEKLY_FIGHT]: 'Weekly Fight',
  };

export const MONSTER_LIST_CATEGORY_VALUES: MonsterListCategory[] = [
  MonsterListCategory.ALL,
  MonsterListCategory.REGULAR,
  MonsterListCategory.RAID_MONSTER,
  MonsterListCategory.RAID_BOSS,
  MonsterListCategory.CELESTIAL,
  MonsterListCategory.SPECIAL_LOCATION,
  MonsterListCategory.WEEKLY_FIGHT,
];

/**
 * Categories for which the Location Type filter is applicable.
 */
export const MONSTER_LIST_CATEGORIES_WITH_LOCATION_TYPE: MonsterListCategory[] =
  [MonsterListCategory.SPECIAL_LOCATION, MonsterListCategory.WEEKLY_FIGHT];

/**
 * Narrow a Dropdown's generic `string | number` selection value down to a
 * known Monster list category, without a forced type assertion at each call
 * site.
 */
export const isMonsterListCategory = (
  value: string | number
): value is MonsterListCategory => {
  if (typeof value !== 'string') {
    return false;
  }

  return MONSTER_LIST_CATEGORY_VALUES.some((category) => category === value);
};

/**
 * The fixed set of Location Types that make up the Weekly Fight category.
 * Cave of Memories is intentionally excluded and remains under Special
 * Location. Mirrors `MonsterListCategory::weeklyFightLocationTypes()` on the
 * backend.
 */
export const MONSTER_LIST_WEEKLY_FIGHT_LOCATION_TYPES: LocationType[] = [
  LocationType.ALCHEMY_CHURCH,
  LocationType.LORDS_STRONG_HOLD,
  LocationType.BROKEN_ANVIL,
  LocationType.TWISTED_MAIDENS_DUNGEONS,
];
