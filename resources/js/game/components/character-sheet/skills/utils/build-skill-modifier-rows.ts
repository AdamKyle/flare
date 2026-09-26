import { formatSkillPercentage } from './format-skill-percentage';
import SkillDetailDefinition from '../api/definitions/skill-detail-definition';
import SkillFactRowDefinition from '../types/skill-fact-row-definition';

type SkillModifierKey =
  | 'unit_time_reduction'
  | 'building_time_reduction'
  | 'unit_movement_time_reduction'
  | 'base_damage_mod'
  | 'base_healing_mod'
  | 'base_ac_mod'
  | 'fight_timeout_mod'
  | 'move_timeout_mod';

const SKILL_MODIFIER_LABELS: Record<SkillModifierKey, string> = {
  unit_time_reduction: 'Unit Time Reduction',
  building_time_reduction: 'Building Time Reduction',
  unit_movement_time_reduction: 'Unit Movement Time Reduction',
  base_damage_mod: 'Base Damage Modifier',
  base_healing_mod: 'Base Healing Modifier',
  base_ac_mod: 'Base AC Modifier',
  fight_timeout_mod: 'Fight Timeout Reduction',
  move_timeout_mod: 'Move Timeout Reduction',
};

const SKILL_MODIFIER_KEYS: SkillModifierKey[] = [
  'unit_time_reduction',
  'building_time_reduction',
  'unit_movement_time_reduction',
  'base_damage_mod',
  'base_healing_mod',
  'base_ac_mod',
  'fight_timeout_mod',
  'move_timeout_mod',
];

export const buildSkillModifierRows = (
  skill: SkillDetailDefinition
): SkillFactRowDefinition[] =>
  SKILL_MODIFIER_KEYS.filter((key) => skill[key] > 0).map((key) => ({
    key,
    label: SKILL_MODIFIER_LABELS[key],
    value: formatSkillPercentage(skill[key]),
  }));
