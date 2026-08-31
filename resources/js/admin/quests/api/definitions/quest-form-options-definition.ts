export interface QuestFormOptionItem {
  value: number;
  label: string;
}

export default interface QuestFormOptionsDefinition {
  npcs: QuestFormOptionItem[];
  quest_items: QuestFormOptionItem[];
  quests: QuestFormOptionItem[];
  game_maps: QuestFormOptionItem[];
  raids: QuestFormOptionItem[];
  passive_skills: QuestFormOptionItem[];
  skill_types: number[];
  feature_types: number[];
  event_types: number[];
}
