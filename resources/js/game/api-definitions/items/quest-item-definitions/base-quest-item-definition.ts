import {
  LocationIdentityDefinition,
  MonsterIdentityDefinition,
  QuestIdentityDefinition,
} from '../../../reusable-components/quest-item/types/quest-item-factual-definition';
import { BaseItemDetails } from '../base-item-details';

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
  drop_location: LocationIdentityDefinition;
  required_monsters: MonsterIdentityDefinition[];
  required_quest: QuestIdentityDefinition | null;
  reward_locations: LocationIdentityDefinition[];
  required_quests: QuestIdentityDefinition[];
  reward_quests: QuestIdentityDefinition[];
  required_locations: LocationIdentityDefinition[];
  min_list_price: number;
}
