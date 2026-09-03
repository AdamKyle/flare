import { ReactNode } from 'react';

import TreeColor from '../enums/tree-color';

/**
 * Internal data given to the generic Tree's React Flow custom node. Never
 * imported outside `resources/js/ui/tree/**`. The index signature is a
 * required adaptation to satisfy `@xyflow/react`'s `NodeData extends
 * Record<string, unknown>` generic constraint.
 */
export default interface TreeFlowNodeDataDefinition {
  content: ReactNode;
  color: TreeColor;
  accessible_label: string;
  on_activate?: () => void;
  [key: string]: unknown;
}
