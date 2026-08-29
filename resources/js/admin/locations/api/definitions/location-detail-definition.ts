import { LocationType } from '../../enums/location-type';

export interface LocationDetailRelatedItemDefinition {
  id: number;
  name: string;
}

export default interface LocationDetailDefinition {
  id: number;
  game_map: { id: number; name: string };
  name: string;
  description: string | null;
  type: LocationType | null;
  x: number;
  y: number;
  pin_css_class: string | null;
  is_port: boolean;
  can_players_enter: boolean;
  can_auto_battle: boolean;
  required_quest_item: LocationDetailRelatedItemDefinition | null;
  quest_reward_item: LocationDetailRelatedItemDefinition | null;
  hours_to_drop: number | null;
  minutes_between_delve_fights: number | null;
  quest_item_drop_count: number;
  is_cave_of_memories: boolean;
  manual_fighting_only: boolean;
}
