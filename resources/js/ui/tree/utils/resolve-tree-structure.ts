import TreeBranchDefinition from '../definitions/tree-branch-definition';
import TreeNodeDefinition from '../definitions/tree-node-definition';
import TreeStructureDefinition from '../definitions/tree-structure-definition';

/**
 * Resolve the generic parent/child hierarchy metadata for a Tree from its
 * flat nodes and explicit branches: parent id per node, child count per
 * node, one-based hierarchy level per node (breadth-first from every root),
 * root count, and maximum depth. Assumes already-validated hierarchy input:
 * `Tree` calls `isTreeStructureValid` first and never invokes this resolver
 * when the structure is malformed (duplicate node ids, a branch referencing
 * an unknown node, a node with more than one parent, or a cycle), so every
 * branch here always references a known node and the hierarchy is acyclic.
 *
 * @param  nodes  Already-validated Tree nodes to resolve structure for.
 * @param  branches  Already-validated explicit parent/child Tree branches.
 * @return  Resolved generic Tree structural metadata.
 */
export const resolveTreeStructure = <TData>(
  nodes: Array<TreeNodeDefinition<TData>>,
  branches: TreeBranchDefinition[]
): TreeStructureDefinition => {
  const nodeIds = new Set(nodes.map((node) => node.id));
  const parentIdByNodeId = new Map<string, string | null>();
  const childCountByNodeId = new Map<string, number>();
  const childIdsByParentId = new Map<string, string[]>();

  nodes.forEach((node) => {
    parentIdByNodeId.set(node.id, null);
    childCountByNodeId.set(node.id, 0);
  });

  branches.forEach((branch) => {
    if (!nodeIds.has(branch.source_id) || !nodeIds.has(branch.target_id)) {
      return;
    }

    parentIdByNodeId.set(branch.target_id, branch.source_id);
    childCountByNodeId.set(
      branch.source_id,
      (childCountByNodeId.get(branch.source_id) ?? 0) + 1
    );

    const existingChildIds = childIdsByParentId.get(branch.source_id) ?? [];
    existingChildIds.push(branch.target_id);
    childIdsByParentId.set(branch.source_id, existingChildIds);
  });

  const levelByNodeId = new Map<string, number>();
  const roots = nodes.filter((node) => parentIdByNodeId.get(node.id) === null);
  const queue: string[] = [];

  roots.forEach((root) => {
    levelByNodeId.set(root.id, 1);
    queue.push(root.id);
  });

  while (queue.length > 0) {
    const currentId = queue.shift();

    if (currentId === undefined) {
      break;
    }

    const currentLevel = levelByNodeId.get(currentId) ?? 1;
    const childIds = childIdsByParentId.get(currentId) ?? [];

    childIds.forEach((childId) => {
      if (levelByNodeId.has(childId)) {
        return;
      }

      levelByNodeId.set(childId, currentLevel + 1);
      queue.push(childId);
    });
  }

  nodes.forEach((node) => {
    if (!levelByNodeId.has(node.id)) {
      levelByNodeId.set(node.id, 1);
    }
  });

  const maxDepth =
    levelByNodeId.size === 0 ? 0 : Math.max(...levelByNodeId.values());

  return {
    parent_id_by_node_id: parentIdByNodeId,
    child_count_by_node_id: childCountByNodeId,
    level_by_node_id: levelByNodeId,
    root_count: roots.length,
    max_depth: maxDepth,
  };
};
