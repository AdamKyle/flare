export enum SkillType {
  TRAINING = 0,
  CRAFTING = 1,
  ENCHANTING = 2,
  DISENCHANTING = 3,
  ALCHEMY = 4,
  EFFECTS_BATTLE_TIMER = 5,
  EFFECTS_DIRECTIONAL_MOVE_TIMER = 6,
  EFFECTS_MOVEMENT_TIMER = 7,
  EFFECTS_KINGDOM_BUILDING_TIMERS = 8,
  EFFECTS_UNIT_RECRUITMENT_TIMER = 9,
  EFFECTS_UNIT_MOVEMENT_TIMER = 10,
  EFFECTS_SPELL_EVASION = 11,
  EFFECTS_KINGDOM = 12,
  EFFECTS_CLASS = 13,
  GEM_CRAFTING = 14,
}

const SKILL_TYPE_LABELS: Record<SkillType, string> = {
  [SkillType.TRAINING]: 'Training',
  [SkillType.CRAFTING]: 'Crafting',
  [SkillType.ENCHANTING]: 'Enchanting',
  [SkillType.DISENCHANTING]: 'Disenchanting',
  [SkillType.ALCHEMY]: 'Alchemy',
  [SkillType.EFFECTS_BATTLE_TIMER]: 'Effects Battle Timer',
  [SkillType.EFFECTS_DIRECTIONAL_MOVE_TIMER]: 'Effects Directional Move Timer',
  [SkillType.EFFECTS_MOVEMENT_TIMER]: 'Effects Movement Timer',
  [SkillType.EFFECTS_KINGDOM_BUILDING_TIMERS]:
    'Effects Kingdom Building Timers',
  [SkillType.EFFECTS_UNIT_RECRUITMENT_TIMER]: 'Effects Unit Recruitment Timers',
  [SkillType.EFFECTS_UNIT_MOVEMENT_TIMER]: 'Effects Unit Movement Timers',
  [SkillType.EFFECTS_SPELL_EVASION]: 'Effects Spell Evasion',
  [SkillType.EFFECTS_KINGDOM]: 'Effects Kingdoms',
  [SkillType.EFFECTS_CLASS]: 'Effects Class',
  [SkillType.GEM_CRAFTING]: 'Gem Crafting',
};

export const isSkillType = (value: number | null): value is SkillType =>
  value !== null && value in SKILL_TYPE_LABELS;

export const skillTypeLabel = (value: number): string => {
  if (!isSkillType(value)) {
    return `Unknown Skill Type (${value})`;
  }

  return SKILL_TYPE_LABELS[value];
};
