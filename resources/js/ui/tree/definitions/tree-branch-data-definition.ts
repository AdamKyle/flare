import TreeColor from '../enums/tree-color';

/**
 * Internal data given to the generic Tree's React Flow custom edge. Never
 * imported outside `resources/js/ui/tree/**`. The index signature is a
 * required adaptation to satisfy `@xyflow/react`'s `EdgeData extends
 * Record<string, unknown>` generic constraint.
 */
export default interface TreeBranchDataDefinition {
  color: TreeColor;
  [key: string]: unknown;
}
