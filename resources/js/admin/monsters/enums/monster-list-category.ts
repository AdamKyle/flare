import { LocationType } from '../../locations/enums/location-type';

export enum MonsterListCategory {
  ALL = 'all',
  REGULAR = 'regular',
  RAID_MONSTER = 'raid_monster',
  RAID_BOSS = 'raid_boss',
  CELESTIAL = 'celestial',
  WEEKLY_FIGHT = 'weekly_fight',
}

export const MONSTER_LIST_CATEGORY_LABELS: Record<MonsterListCategory, string> =
  {
    [MonsterListCategory.ALL]: 'All Monsters',
    [MonsterListCategory.REGULAR]: 'Regular',
    [MonsterListCategory.RAID_MONSTER]: 'Raid Monsters',
    [MonsterListCategory.RAID_BOSS]: 'Raid Bosses',
    [MonsterListCategory.CELESTIAL]: 'Celestials',
    [MonsterListCategory.WEEKLY_FIGHT]: 'Weekly Fight',
  };

export const MONSTER_LIST_CATEGORY_VALUES: MonsterListCategory[] = [
  MonsterListCategory.ALL,
  MonsterListCategory.REGULAR,
  MonsterListCategory.RAID_MONSTER,
  MonsterListCategory.RAID_BOSS,
  MonsterListCategory.CELESTIAL,
  MonsterListCategory.WEEKLY_FIGHT,
];

export const MONSTER_LIST_CATEGORIES_WITH_LOCATION_TYPE: MonsterListCategory[] =
  [MonsterListCategory.WEEKLY_FIGHT];

export const isMonsterListCategory = (
  value: string | number
): value is MonsterListCategory => {
  if (typeof value !== 'string') {
    return false;
  }

  return MONSTER_LIST_CATEGORY_VALUES.some((category) => category === value);
};

export const MONSTER_LIST_WEEKLY_FIGHT_LOCATION_TYPES: LocationType[] = [
  LocationType.ALCHEMY_CHURCH,
  LocationType.LORDS_STRONG_HOLD,
  LocationType.BROKEN_ANVIL,
  LocationType.TWISTED_MAIDENS_DUNGEONS,
  LocationType.THE_CELLAR,
];
