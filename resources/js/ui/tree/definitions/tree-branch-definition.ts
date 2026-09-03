import TreeColor from '../enums/tree-color';

/**
 * Generic explicit Tree branch (parent/child connection) contract. Callers
 * never construct a React Flow `Edge` directly.
 */
export default interface TreeBranchDefinition {
  id: string;
  source_id: string;
  target_id: string;
  color?: TreeColor;
}
