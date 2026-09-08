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
