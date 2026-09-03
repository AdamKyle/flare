import TreeBranchDefinition from '../definitions/tree-branch-definition';
import TreeNodeDefinition from '../definitions/tree-node-definition';

/**
 * Validate a generic Tree's flat nodes and explicit branches before layout.
 * Rejects (returns `false` for): duplicate node ids, a branch referencing an
 * unknown source/target node id, a node with more than one parent, and any
 * hierarchy cycle. Never silently repairs malformed data — a caller with an
 * invalid structure must render a generic accessible fallback instead of
 * proceeding to layout.
 *
 * @param  nodes  Tree nodes to validate.
 * @param  branches  Explicit parent/child Tree branches to validate.
 * @return  Whether the supplied structure is a valid Tree/forest.
 */
export const isTreeStructureValid = <TData>(
  nodes: Array<TreeNodeDefinition<TData>>,
  branches: TreeBranchDefinition[]
): boolean => {
  const seenNodeIds = new Set<string>();

  for (const node of nodes) {
    if (seenNodeIds.has(node.id)) {
      return false;
    }

    seenNodeIds.add(node.id);
  }

  const parentIdByNodeId = new Map<string, string>();

  for (const branch of branches) {
    if (
      !seenNodeIds.has(branch.source_id) ||
      !seenNodeIds.has(branch.target_id)
    ) {
      return false;
    }

    if (parentIdByNodeId.has(branch.target_id)) {
      return false;
    }

    parentIdByNodeId.set(branch.target_id, branch.source_id);
  }

  for (const node of nodes) {
    const visitedIds = new Set<string>();
    let currentId: string | undefined = node.id;

    while (currentId !== undefined) {
      if (visitedIds.has(currentId)) {
        return false;
      }

      visitedIds.add(currentId);
      currentId = parentIdByNodeId.get(currentId);
    }
  }

  return true;
};
