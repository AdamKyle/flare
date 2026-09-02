import { QuestTreeNavigationDefinition } from './quest-node-props';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';

export default interface QuestMobileListProps {
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
  navigation?: QuestTreeNavigationDefinition;
}
