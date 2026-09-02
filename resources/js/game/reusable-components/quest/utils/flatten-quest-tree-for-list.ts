import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import FlattenedQuestEntry from '../types/flattened-quest-entry';

/**
 * Flatten a Quest tree into a depth-first list, preserving the backend's
 * deterministic ordering, for a non-recursive mobile Quest list
 * presentation. Each entry retains its depth and lightweight ancestor
 * context (immediate parent name, top-level root name) so the flattened
 * card can show useful hierarchy metadata without visual indentation.
 *
 * @param  quests  Root-ordered Quest tree nodes to flatten.
 * @return  Depth-first flattened Quest entries.
 */
export const flattenQuestTreeForList = (
  quests: QuestTreeNodeDefinition[]
): FlattenedQuestEntry[] => {
  const entries: FlattenedQuestEntry[] = [];

  const walk = (
    nodes: QuestTreeNodeDefinition[],
    depth: number,
    parentName: string | null,
    ancestorRootName: string | null
  ): void => {
    nodes.forEach((node) => {
      entries.push({
        quest: node,
        depth,
        parent_name: parentName,
        root_name: depth === 0 ? null : ancestorRootName,
      });

      if (node.children.length > 0) {
        const nextRootName = depth === 0 ? node.name : ancestorRootName;
        walk(node.children, depth + 1, node.name, nextRootName);
      }
    });
  };

  walk(quests, 0, null, null);

  return entries;
};
