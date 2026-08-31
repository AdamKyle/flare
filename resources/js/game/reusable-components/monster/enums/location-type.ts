export enum LocationType {
  PURGATORY_SMITH_HOUSE = 0,
  GOLD_MINES = 1,
  PURGATORY_DUNGEONS = 2,
  UNDERWATER_CAVES = 3,
  TEAR_FABRIC_TIME = 4,
  THE_OLD_CHURCH = 5,
  TWISTED_GATE = 6,
  ALCHEMY_CHURCH = 7,
  LORDS_STRONG_HOLD = 8,
  BROKEN_ANVIL = 9,
  TWISTED_MAIDENS_DUNGEONS = 10,
  CAVE_OF_MEMORIES = 11,
  THE_CELLAR = 12,
  SPECIAL = 13,
}

export const LOCATION_TYPE_LABELS: Record<LocationType, string> = {
  [LocationType.PURGATORY_SMITH_HOUSE]: 'Purgatory Smiths House',
  [LocationType.GOLD_MINES]: 'Gold Mines',
  [LocationType.PURGATORY_DUNGEONS]: 'Purgatory Dungeons',
  [LocationType.UNDERWATER_CAVES]: 'Underwater Caves',
  [LocationType.TEAR_FABRIC_TIME]: 'Tear in the fabrice of time',
  [LocationType.THE_OLD_CHURCH]: 'The Old Church',
  [LocationType.TWISTED_GATE]: 'The Twisted Gate',
  [LocationType.ALCHEMY_CHURCH]: 'Alchemy Church',
  [LocationType.LORDS_STRONG_HOLD]: 'Lords Strong Hold',
  [LocationType.BROKEN_ANVIL]: 'Hells Broken Anvil',
  [LocationType.TWISTED_MAIDENS_DUNGEONS]: 'Twisted Maidens Dungeons',
  [LocationType.CAVE_OF_MEMORIES]: 'Cave of Memories',
  [LocationType.THE_CELLAR]: 'The Cellar',
  [LocationType.SPECIAL]: 'Special',
};

export const isLocationType = (
  value: string | number
): value is LocationType => {
  if (typeof value !== 'number') {
    return false;
  }

  return Object.values(LocationType).some(
    (locationType) => locationType === value
  );
};
