import { QuestTreeNavigationDefinition } from './quest-node-props';
import QuestItemOwnershipState from '../../../components/side-peeks/components/items/enums/quest-item-ownership-state';
import QuestDetailDefinition from '../api/definitions/quest-detail-definition';

export default interface QuestDetailProps {
  quest: QuestDetailDefinition;
  navigation?: QuestTreeNavigationDefinition;
  completed_quest_ids?: number[];
  quest_item_ownership?: Record<number, QuestItemOwnershipState>;
}
