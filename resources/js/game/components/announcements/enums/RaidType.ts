export enum RaidType {
  PIRATE_LORD = 'pirate-lord',
  ICE_QUEEN = 'ice-queen',
  JESTER_OF_TIME = 'jester-of-time',
  FROZEN_KING = 'frozen-king',
  CORRUPTED_BISHOP = 'corrupted-bishop',
  ENRAGED_LITTLE_GIRL = 'enraged-little-girl',
}

const RAID_TYPE_VALUES: RaidType[] = [
  RaidType.PIRATE_LORD,
  RaidType.ICE_QUEEN,
  RaidType.JESTER_OF_TIME,
  RaidType.FROZEN_KING,
  RaidType.CORRUPTED_BISHOP,
  RaidType.ENRAGED_LITTLE_GIRL,
];

/**
 * Narrow a factual `string` value down to a known Raid type, without a
 * forced type assertion at each call site.
 */
export const isRaidType = (value: string): value is RaidType =>
  RAID_TYPE_VALUES.some((raidType) => raidType === value);
