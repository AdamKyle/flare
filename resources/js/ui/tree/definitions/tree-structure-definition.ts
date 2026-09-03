/**
 * Generic structural metadata resolved from a Tree's nodes and branches,
 * used to augment interactive node accessible names with hierarchy
 * level/parent/child-count context and to describe overall Tree scale.
 */
export default interface TreeStructureDefinition {
  parent_id_by_node_id: Map<string, string | null>;
  child_count_by_node_id: Map<string, number>;
  level_by_node_id: Map<string, number>;
  root_count: number;
  max_depth: number;
}
