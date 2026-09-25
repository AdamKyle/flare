import ItemSkillDefinition from '../../../../../../api-definitions/items/item-skill-definition';
import ItemSkillProgressionDefinition from '../../../../../../api-definitions/items/item-skill-progression-definition';
import ItemSkillModifierRow from '../types/item-skill-modifier-row';

import { isDisplayedAsZeroPercent } from 'game-utils/format-number';

export const buildItemSkillModifierRows = (
  progression: ItemSkillProgressionDefinition,
  skill: ItemSkillDefinition
): ItemSkillModifierRow[] =>
  [
    {
      label: 'Str Modifier',
      current: progression.str_mod,
      per_level: skill.str_mod,
    },
    {
      label: 'Dex Modifier',
      current: progression.dex_mod,
      per_level: skill.dex_mod,
    },
    {
      label: 'Dur Modifier',
      current: progression.dur_mod,
      per_level: skill.dur_mod,
    },
    {
      label: 'Agi Modifier',
      current: progression.agi_mod,
      per_level: skill.agi_mod,
    },
    {
      label: 'Int Modifier',
      current: progression.int_mod,
      per_level: skill.int_mod,
    },
    {
      label: 'Chr Modifier',
      current: progression.chr_mod,
      per_level: skill.chr_mod,
    },
    {
      label: 'Focus Modifier',
      current: progression.focus_mod,
      per_level: skill.focus_mod,
    },
    {
      label: 'Damage Modifier',
      current: progression.base_damage_mod,
      per_level: skill.base_damage_mod,
    },
    {
      label: 'AC Modifier',
      current: progression.base_ac_mod,
      per_level: skill.base_ac_mod,
    },
    {
      label: 'Healing Modifier',
      current: progression.base_healing_mod,
      per_level: skill.base_healing_mod,
    },
  ].filter(
    (row) =>
      !isDisplayedAsZeroPercent(row.current) ||
      !isDisplayedAsZeroPercent(row.per_level)
  );

const describeDirection = (value: number): string =>
  value > 0 ? 'increases' : 'decreases';

const describePercent = (value: number): string =>
  `${Math.abs(value * 100).toFixed(2)} percent`;

export const describeCurrentModifier = (
  label: string,
  value: number
): string => {
  if (value === 0) {
    return `${label} has no bonus at the current level`;
  }

  return `${label} ${describeDirection(value)} by ${describePercent(value)}`;
};

export const describePerLevelModifier = (
  label: string,
  value: number
): string => {
  if (value === 0) {
    return `${label} does not change per level`;
  }

  return `${label} ${describeDirection(value)} by ${describePercent(value)} per level`;
};
