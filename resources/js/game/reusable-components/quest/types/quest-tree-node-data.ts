import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import { QuestTreeState } from '../enums/quest-tree-state';

export default interface QuestTreeNodeData {
  quest: QuestTreeNodeDefinition;
  state: QuestTreeState;
}
