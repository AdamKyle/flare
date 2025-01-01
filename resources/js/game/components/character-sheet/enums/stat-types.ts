import { match } from 'ts-pattern';

export enum StatTypes {
  STR,
  DEX,
  INT,
  DUR,
  AGI,
  CHR,
  FOCUS,
}

export const getStatName = (statType: StatTypes | null): string => {
  return match(statType)
    .with(StatTypes.STR, () => 'Strength')
    .with(StatTypes.DEX, () => 'Dexterity')
    .with(StatTypes.INT, () => 'Intelligence')
    .with(StatTypes.DUR, () => 'Durability')
    .with(StatTypes.AGI, () => 'Agility')
    .with(StatTypes.CHR, () => 'Charisma')
    .with(StatTypes.FOCUS, () => 'Focus')
    .otherwise(() => 'Unknown stat type');
};
