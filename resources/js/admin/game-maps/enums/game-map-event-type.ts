export enum GameMapEventType {
  WEEKLY_CELESTIALS = 0,
  WEEKLY_CURRENCY_DROPS = 1,
  RAID_EVENT = 2,
  WINTER_EVENT = 3,
  PURGATORY_SMITH_HOUSE = 4,
  GOLD_MINES = 5,
  THE_OLD_CHURCH = 6,
  DELUSIONAL_MEMORIES_EVENT = 7,
  WEEKLY_FACTION_LOYALTY_EVENT = 8,
}

export const GAME_MAP_EVENT_TYPE_LABELS: Record<GameMapEventType, string> = {
  [GameMapEventType.WEEKLY_CELESTIALS]: 'Weekly Celestials',
  [GameMapEventType.WEEKLY_CURRENCY_DROPS]: 'Weekly Currency Drops',
  [GameMapEventType.RAID_EVENT]: 'Raid Event',
  [GameMapEventType.WINTER_EVENT]: 'Winter Event',
  [GameMapEventType.PURGATORY_SMITH_HOUSE]: 'Purgatory Smith House',
  [GameMapEventType.GOLD_MINES]: 'Gold Mines',
  [GameMapEventType.THE_OLD_CHURCH]: 'The Old Church',
  [GameMapEventType.DELUSIONAL_MEMORIES_EVENT]: 'Delusional Memories Event',
  [GameMapEventType.WEEKLY_FACTION_LOYALTY_EVENT]:
    'Weekly Faction Loyalty Event',
};

export const GAME_MAP_EVENT_TYPE_VALUES: GameMapEventType[] = [
  GameMapEventType.WEEKLY_CELESTIALS,
  GameMapEventType.WEEKLY_CURRENCY_DROPS,
  GameMapEventType.RAID_EVENT,
  GameMapEventType.WINTER_EVENT,
  GameMapEventType.PURGATORY_SMITH_HOUSE,
  GameMapEventType.GOLD_MINES,
  GameMapEventType.THE_OLD_CHURCH,
  GameMapEventType.DELUSIONAL_MEMORIES_EVENT,
  GameMapEventType.WEEKLY_FACTION_LOYALTY_EVENT,
];

/**
 * Narrow a Dropdown's generic `string | number` selection value down to a
 * known Game Map event type, without a forced type assertion at each call site.
 */
export const isGameMapEventType = (
  value: string | number
): value is GameMapEventType => {
  if (typeof value !== 'number') {
    return false;
  }

  return GAME_MAP_EVENT_TYPE_VALUES.some((eventType) => eventType === value);
};
