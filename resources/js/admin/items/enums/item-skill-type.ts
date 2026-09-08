export enum ItemSkillType {
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

export const ITEM_SKILL_TYPE_LABELS: Record<ItemSkillType, string> = {
  [ItemSkillType.TRAINING]: 'Training',
  [ItemSkillType.CRAFTING]: 'Crafting',
  [ItemSkillType.ENCHANTING]: 'Enchanting',
  [ItemSkillType.DISENCHANTING]: 'Disenchanting',
  [ItemSkillType.ALCHEMY]: 'Alchemy',
  [ItemSkillType.EFFECTS_BATTLE_TIMER]: 'Effects Battle Timer',
  [ItemSkillType.EFFECTS_DIRECTIONAL_MOVE_TIMER]:
    'Effects Directional Move Timer',
  [ItemSkillType.EFFECTS_MOVEMENT_TIMER]: 'Effects Movement Timer',
  [ItemSkillType.EFFECTS_KINGDOM_BUILDING_TIMERS]:
    'Effects Kingdom Building Timers',
  [ItemSkillType.EFFECTS_UNIT_RECRUITMENT_TIMER]:
    'Effects Unit Recruitment Timers',
  [ItemSkillType.EFFECTS_UNIT_MOVEMENT_TIMER]: 'Effects Unit Movement Timers',
  [ItemSkillType.EFFECTS_SPELL_EVASION]: 'Effects Spell Evasion',
  [ItemSkillType.EFFECTS_KINGDOM]: 'Effects Kingdoms',
  [ItemSkillType.EFFECTS_CLASS]: 'Effects Class',
  [ItemSkillType.GEM_CRAFTING]: 'Gem Crafting',
};

export const ITEM_SKILL_TYPE_VALUES: ItemSkillType[] = [
  ItemSkillType.TRAINING,
  ItemSkillType.CRAFTING,
  ItemSkillType.ENCHANTING,
  ItemSkillType.DISENCHANTING,
  ItemSkillType.ALCHEMY,
  ItemSkillType.EFFECTS_BATTLE_TIMER,
  ItemSkillType.EFFECTS_DIRECTIONAL_MOVE_TIMER,
  ItemSkillType.EFFECTS_MOVEMENT_TIMER,
  ItemSkillType.EFFECTS_KINGDOM_BUILDING_TIMERS,
  ItemSkillType.EFFECTS_UNIT_RECRUITMENT_TIMER,
  ItemSkillType.EFFECTS_UNIT_MOVEMENT_TIMER,
  ItemSkillType.EFFECTS_SPELL_EVASION,
  ItemSkillType.EFFECTS_KINGDOM,
  ItemSkillType.EFFECTS_CLASS,
  ItemSkillType.GEM_CRAFTING,
];

export const isItemSkillType = (
  value: string | number
): value is ItemSkillType => {
  if (typeof value !== 'number') {
    return false;
  }

  return ITEM_SKILL_TYPE_VALUES.some((skillType) => skillType === value);
};
