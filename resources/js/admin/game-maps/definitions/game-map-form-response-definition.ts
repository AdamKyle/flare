import { GameMapEventType } from '../enums/game-map-event-type';

export default interface GameMapFormResponseDefinition {
  id: number;
  name: string;
  description: string | null;
  map_url: string;
  kingdom_color: string;
  default: boolean;
  can_traverse: boolean;
  only_during_event_type: GameMapEventType | null;
  xp_bonus: number;
  skill_training_bonus: number;
  drop_chance_bonus: number;
  enemy_stat_bonus: number;
  character_attack_reduction: number;
  required_location_id: number | null;
}
