import { resolveQuestTreeState } from './resolve-quest-tree-state';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import {
  QUEST_TREE_STATE_LABELS,
  QuestTreeState,
} from '../enums/quest-tree-state';
import { QUEST_TREE_STATE_TREE_COLOR } from '../styles/quest-tree-state-styles';
import QuestTreeData from '../types/quest-tree-data';
import QuestTreeNodeData from '../types/quest-tree-node-data';

import TreeBranchDefinition from 'ui/tree/definitions/tree-branch-definition';
import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';
import TreeColor from 'ui/tree/enums/tree-color';

/**
 * Build the factual accessible domain label for one Quest Tree node: Quest
 * name, full structural state label, Quest Giver when present, and child
 * Quest count. The generic Tree layer separately augments this with
 * hierarchy level/parent/child structure, so state is never exposed as
 * color alone.
 *
 * @param  quest  Source Quest tree node.
 * @param  state  Resolved structural Quest state.
 * @return  Factual accessible label for the Quest Tree node.
 */
const buildQuestAccessibilityLabel = (
  quest: QuestTreeNodeDefinition,
  state: QuestTreeState
): string => {
  const segments = [quest.name, `${QUEST_TREE_STATE_LABELS[state]}.`];

  if (quest.npc) {
    segments.push(`Quest Giver: ${quest.npc.name}.`);
  }

  const childCount = quest.children.length;
  segments.push(
    childCount === 1 ? '1 child Quest.' : `${childCount} child Quests.`
  );

  return segments.join(' ');
};

/**
 * Convert the existing recursive Quest tree API response into the generic
 * Tree's flat nodes and explicit branches, built depth-first in the
 * existing API order. Branches represent the existing `parent_quest_id`
 * hierarchy only; no second hierarchy model is derived from required
 * Quests.
 *
 * @param  quests  Root-ordered Quest tree nodes from the existing API response.
 * @param  completedQuestIds  Completed Quest ids used to resolve structural state.
 * @return  Flat generic Tree nodes and explicit branches for the Quest tree.
 */
export const buildQuestTreeData = (
  quests: QuestTreeNodeDefinition[],
  completedQuestIds: ReadonlySet<number>
): QuestTreeData => {
  const nodes: Array<TreeNodeDefinition<QuestTreeNodeData>> = [];
  const branches: TreeBranchDefinition[] = [];

  const walk = (quest: QuestTreeNodeDefinition): void => {
    const state = resolveQuestTreeState(quest, completedQuestIds);

    nodes.push({
      id: String(quest.id),
      label: quest.name,
      data: { quest, state },
      color: QUEST_TREE_STATE_TREE_COLOR[state],
      is_available: state === QuestTreeState.AVAILABLE,
      accessibility_label: buildQuestAccessibilityLabel(quest, state),
    });

    quest.children.forEach((child) => {
      branches.push({
        id: `${quest.id}-${child.id}`,
        source_id: String(quest.id),
        target_id: String(child.id),
        color: TreeColor.DANUBE,
      });

      walk(child);
    });
  };

  quests.forEach(walk);

  return { nodes, branches };
};
