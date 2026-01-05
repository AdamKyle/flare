import { match } from 'ts-pattern';

export enum StatTypes {
  STR,
  DEX,
  INT,
  DUR,
  AGI,
  CHR,
  FOCUS,
  BASE_DAMAGE,
  BASE_HEALING,
  BASE_AC,
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
    .with(StatTypes.BASE_DAMAGE, () => 'Base Damage Mod')
    .with(StatTypes.BASE_HEALING, () => 'Base Healing Mod')
    .with(StatTypes.BASE_AC, () => 'Base Ac Mod')
    .otherwise(() => 'Unknown stat type');
};

export const getStatAbbreviation = (statType: StatTypes): string => {
  return match(statType)
    .with(StatTypes.STR, () => 'str')
    .with(StatTypes.DEX, () => 'dex')
    .with(StatTypes.INT, () => 'int')
    .with(StatTypes.DUR, () => 'dur')
    .with(StatTypes.AGI, () => 'agi')
    .with(StatTypes.CHR, () => 'chr')
    .with(StatTypes.FOCUS, () => 'focus')
    .with(StatTypes.BASE_DAMAGE, () => 'base_damage')
    .with(StatTypes.BASE_AC, () => 'base_ac')
    .with(StatTypes.BASE_HEALING, () => 'base_healing')
    .otherwise(() => 'Unknown');
};
