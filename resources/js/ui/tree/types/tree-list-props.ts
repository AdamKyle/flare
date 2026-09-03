import { ReactNode } from 'react';

import TreeNodeDefinition from '../definitions/tree-node-definition';

export default interface TreeListProps<TData> {
  nodes: Array<TreeNodeDefinition<TData>>;
  render_node: (node: TreeNodeDefinition<TData>) => ReactNode;
  render_list_node?: (node: TreeNodeDefinition<TData>) => ReactNode;
  on_node_activate?: (node: TreeNodeDefinition<TData>) => void;
  available_empty_state?: ReactNode;
}
