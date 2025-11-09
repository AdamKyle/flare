import GuideQuestDefinition from './guide-quest-definition';

export default interface GuideQuestResponseDefinition {
  guide_quest: GuideQuestDefinition;
  game_skills: { [key: number]: string };
  faction_maps: { [key: number]: string };
  quests: { [key: number]: string };
  quest_items: { [key: number]: string };
  passives: { [key: number]: string };
  skill_types: string[];
  kingdom_buildings: { [key: number]: string };
  events: string[];
  guide_quests: { [key: number]: string } | null;
  game_maps: { [key: number]: string };
  item_specialty_types: { [key: string]: string };
}
