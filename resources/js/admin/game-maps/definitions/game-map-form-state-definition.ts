import { GameMapEventType } from '../enums/game-map-event-type';

export default interface GameMapFormStateDefinition {
  name: string;
  description: string;
  kingdom_color: string;
  default: boolean;
  map: File | null;
  replacement_image_acknowledged: boolean;
  xp_bonus: string;
  skill_training_bonus: string;
  drop_chance_bonus: string;
  enemy_stat_bonus: string;
  character_attack_reduction: string;
  required_location_id: number | null;
  can_traverse: boolean;
  only_during_event_type: GameMapEventType | null;
}
