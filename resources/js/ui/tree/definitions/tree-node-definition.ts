import TreeColor from '../enums/tree-color';

/**
 * Generic Tree node contract. `label` is the short structural identity;
 * `accessibility_label` is the domain-supplied meaningful description/state
 * text. `is_available` exists specifically so the generic
 * `ONLY_WHATS_AVAILABLE` responsive mode can filter to available nodes for
 * any current or future Tree consumer.
 */
export default interface TreeNodeDefinition<TData> {
  id: string;
  label: string;
  data: TData;
  color: TreeColor;
  is_available: boolean;
  accessibility_label: string;
  width?: number;
  height?: number;
}
