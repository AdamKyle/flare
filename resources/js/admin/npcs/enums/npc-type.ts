export enum NpcType {
  KINGDOM_HOLDER = 0,
  SUMMONER = 1,
  QUEST_GIVER = 2,
  SPECIAL_ENCHANTS = 3,
}

export const NPC_TYPE_LABELS: Record<NpcType, string> = {
  [NpcType.KINGDOM_HOLDER]: 'Kingdom Holder',
  [NpcType.SUMMONER]: 'Summoner',
  [NpcType.QUEST_GIVER]: 'Quest Giver',
  [NpcType.SPECIAL_ENCHANTS]: 'Special Enchantments',
};

export const NPC_TYPE_VALUES: NpcType[] = [
  NpcType.KINGDOM_HOLDER,
  NpcType.SUMMONER,
  NpcType.QUEST_GIVER,
  NpcType.SPECIAL_ENCHANTS,
];

/**
 * Narrow a Dropdown's generic `string | number` selection value down to a
 * known NPC type, without a forced type assertion at each call site.
 */
export const isNpcType = (value: string | number): value is NpcType => {
  if (typeof value !== 'number') {
    return false;
  }

  return NPC_TYPE_VALUES.some((npcType) => npcType === value);
};
