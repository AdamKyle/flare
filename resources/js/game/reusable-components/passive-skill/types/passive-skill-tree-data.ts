import PassiveSkillTreeNodeData from './passive-skill-tree-node-data';

import TreeBranchDefinition from 'ui/tree/definitions/tree-branch-definition';
import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';

export default interface PassiveSkillTreeData {
  nodes: Array<TreeNodeDefinition<PassiveSkillTreeNodeData>>;
  branches: TreeBranchDefinition[];
}
