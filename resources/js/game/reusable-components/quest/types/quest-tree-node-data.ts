import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import { QuestTreeState } from '../enums/quest-tree-state';

/**
 * The generic Tree node's opaque `TData` payload for the Quest domain: the
 * exact source Quest tree node plus its resolved structural state.
 */
export default interface QuestTreeNodeData {
  quest: QuestTreeNodeDefinition;
  state: QuestTreeState;
}
