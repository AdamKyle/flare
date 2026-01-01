import { match } from 'ts-pattern';

export enum EventType {
  WEEKLY_CELESTIALS = 0,
  WEEKLY_CURRENCY_DROPS = 1,
  RAID_EVENT = 2,
  WINTER_EVENT = 3,
  PURGATORY_SMITH_HOUSE = 4,
  GOLD_MINES = 5,
  THE_OLD_CHURCH = 6,
  DELUSIONAL_MEMORIES_EVENT = 7,
  WEEKLY_FACTION_LOYALTY_EVENT = 8,
  FEEDBACK_EVENT = 9,
}

export const getEventTypeName = (eventType: EventType): string =>
  match(eventType)
    .with(EventType.WEEKLY_CELESTIALS, () => 'Weekly Celestials')
    .with(EventType.WEEKLY_CURRENCY_DROPS, () => 'Weekly Currency Drops')
    .with(EventType.RAID_EVENT, () => 'Raid Event')
    .with(EventType.WINTER_EVENT, () => 'Winter Event')
    .with(EventType.PURGATORY_SMITH_HOUSE, () => 'Purgatory Smith House')
    .with(EventType.GOLD_MINES, () => 'Gold Mines')
    .with(EventType.THE_OLD_CHURCH, () => 'The Old Church')
    .with(
      EventType.DELUSIONAL_MEMORIES_EVENT,
      () => 'Delusional Memories Event'
    )
    .with(
      EventType.WEEKLY_FACTION_LOYALTY_EVENT,
      () => 'Weekly Faction Loyalty Event'
    )
    .with(EventType.FEEDBACK_EVENT, () => "Tlessa's Feedback Event")
    .otherwise(() => 'Unknown Event Name');
