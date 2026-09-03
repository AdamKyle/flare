import QuestTreeNodeData from './quest-tree-node-data';

import TreeBranchDefinition from 'ui/tree/definitions/tree-branch-definition';
import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';

export default interface QuestTreeData {
  nodes: Array<TreeNodeDefinition<QuestTreeNodeData>>;
  branches: TreeBranchDefinition[];
}
