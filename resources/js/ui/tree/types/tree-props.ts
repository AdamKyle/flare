import { ReactNode } from 'react';

import TreeBranchDefinition from '../definitions/tree-branch-definition';
import TreeNodeDefinition from '../definitions/tree-node-definition';
import TreeColor from '../enums/tree-color';
import TreeMobileMode from '../enums/tree-mobile-mode';

export default interface TreeProps<TData> {
  nodes: Array<TreeNodeDefinition<TData>>;
  branches: TreeBranchDefinition[];
  render_node: (node: TreeNodeDefinition<TData>) => ReactNode;
  render_list_node?: (node: TreeNodeDefinition<TData>) => ReactNode;
  on_node_activate?: (node: TreeNodeDefinition<TData>) => void;
  accessibility_label: string;
  mobile_mode?: TreeMobileMode;
  empty_state?: ReactNode;
  available_empty_state?: ReactNode;
  default_node_width?: number;
  default_node_height?: number;
  default_branch_color?: TreeColor;
  default_zoom?: number;
  default_focus_node_id?: string;
}
