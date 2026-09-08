import { GameMapEventType } from '../enums/game-map-event-type';

export interface GameMapRequiredQuestItemQuestDefinition {
  id: number;
  name: string;
}

export interface GameMapRequiredQuestItemDefinition {
  id: number;
  name: string;
  quest: GameMapRequiredQuestItemQuestDefinition | null;
}

export interface GameMapRequiredLocationDefinition {
  id: number;
  name: string;
}

export default interface GameMapFactualDefinition {
  id: number;
  name: string;
  map_url: string;
  description: string | null;
  kingdom_color: string;
  default: boolean;
  can_traverse: boolean;
  event_restriction: GameMapEventType | null;
  xp_bonus: number | null;
  skill_training_bonus: number | null;
  drop_chance_bonus: number | null;
  enemy_stat_bonus: number | null;
  character_attack_reduction: number | null;
  required_location: GameMapRequiredLocationDefinition | null;
  required_quest_item: GameMapRequiredQuestItemDefinition | null;
}
