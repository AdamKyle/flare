export enum CoreStat {
  STRENGTH = 'str',
  DURABILITY = 'dur',
  DEXTERITY = 'dex',
  CHARISMA = 'chr',
  INTELLIGENCE = 'int',
  AGILITY = 'agi',
  FOCUS = 'focus',
}

export const CORE_STAT_LABELS: Record<CoreStat, string> = {
  [CoreStat.STRENGTH]: 'Strength',
  [CoreStat.DURABILITY]: 'Durability',
  [CoreStat.DEXTERITY]: 'Dexterity',
  [CoreStat.CHARISMA]: 'Charisma',
  [CoreStat.INTELLIGENCE]: 'Intelligence',
  [CoreStat.AGILITY]: 'Agility',
  [CoreStat.FOCUS]: 'Focus',
};

const CORE_STAT_VALUES: readonly CoreStat[] = Object.values(CoreStat);

export const isCoreStat = (value: string): value is CoreStat =>
  CORE_STAT_VALUES.some((candidate) => candidate === value);

export const coreStatLabel = (value: string): string =>
  isCoreStat(value) ? CORE_STAT_LABELS[value] : value;
