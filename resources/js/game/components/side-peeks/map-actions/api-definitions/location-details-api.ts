import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { LocationTypes } from '../teleport/enums/location-types';

export default interface LocationDetailsApi {
  id: number;
  name: string;
  description: string;
  can_players_enter: false;
  can_auto_battle: false;
  location_type: LocationTypes;
  is_corrupted: boolean;
  quest_reward_item: { data: BaseQuestItemDefinition | null };
  required_quest_item: BaseQuestItemDefinition | null;
  enemy_strength_increase: number | null;
  x: number;
  y: number;
}
