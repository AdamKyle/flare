import ItemSkillTreeNodeData from './item-skill-tree-node-data';

import TreeBranchDefinition from 'ui/tree/definitions/tree-branch-definition';
import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';

export default interface ItemSkillTreeData {
  nodes: Array<TreeNodeDefinition<ItemSkillTreeNodeData>>;
  branches: TreeBranchDefinition[];
  is_incomplete: boolean;
}
