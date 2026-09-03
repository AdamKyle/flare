import { Edge, Node } from '@xyflow/react';

import TreeBranchDataDefinition from './tree-branch-data-definition';
import TreeFlowNodeDataDefinition from './tree-flow-node-data-definition';

/**
 * Internally generated React Flow nodes/edges produced by `buildTreeLayout`.
 * Never imported outside `resources/js/ui/tree/**`.
 */
export default interface TreeLayoutDefinition {
  flow_nodes: Array<Node<TreeFlowNodeDataDefinition>>;
  flow_edges: Array<Edge<TreeBranchDataDefinition>>;
}
