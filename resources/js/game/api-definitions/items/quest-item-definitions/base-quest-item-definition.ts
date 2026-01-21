import LocationInfoDefinition from './location-info-definition';
import QuestInfoDefinition from './quest-info-definition';
import { BaseItemDetails } from '../base-item-details';
import RequiredMonsterDefinition from './required-monster-definition';

export default interface BaseQuestItemDefinition extends BaseItemDetails {
  item_id: number;
  slot_id: number;
  name: string;
  description: string;
  can_drop: boolean;
  usable: boolean;
  craft_only: boolean;
  move_time_out_mod_bonus: number;
  fight_time_out_mod_bonus: number;
  effect: string;
  drop_location: LocationInfoDefinition;
  required_monster: RequiredMonsterDefinition | null;
  required_quest: QuestInfoDefinition | null;
  reward_locations: LocationInfoDefinition[];
  required_quests: QuestInfoDefinition[];
  reward_quests: QuestInfoDefinition[];
  required_locations: LocationInfoDefinition[];
  min_list_price: number;
}
