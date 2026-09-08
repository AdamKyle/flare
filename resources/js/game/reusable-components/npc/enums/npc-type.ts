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
