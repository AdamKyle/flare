import { QuestTreeNavigationDefinition } from './quest-node-props';
import QuestDetailDefinition from '../api/definitions/quest-detail-definition';

export default interface QuestDetailProps {
  quest: QuestDetailDefinition;
  navigation?: QuestTreeNavigationDefinition;
  completed_quest_ids?: number[];
}
