import React, { ReactNode, useMemo } from 'react';

import QuestTreeListNode from './quest-tree-list-node';
import QuestTreeNode from './quest-tree-node';
import QuestTreeNodeData from '../types/quest-tree-node-data';
import QuestTreeProps from '../types/quest-tree-props';
import { buildQuestTreeData } from '../utils/build-quest-tree-data';

import TreeNodeDefinition from 'ui/tree/definitions/tree-node-definition';
import Tree from 'ui/tree/tree';

/**
 * Shared, permission-neutral Quest tree: a real top-to-bottom hierarchy
 * rendered through the generic shared `Tree`. Mobile presentation defaults
 * to the actual visual Tree; passing `TreeMobileMode.ONLY_WHATS_AVAILABLE`
 * switches mobile to a card list of only currently available Quests (for a
 * future Character adapter), using the presentational `QuestTreeListNode`
 * for that mobile list — the generic Tree itself owns the single
 * interactive activation target for every list item.
 */
const QuestTree = ({
  quests,
  completed_quest_ids: completedQuestIdsList,
  navigation,
  mobile_mode: mobileMode,
  accessibility_label: accessibilityLabel,
}: QuestTreeProps): ReactNode => {
  const completedQuestIds = useMemo(
    () => new Set(completedQuestIdsList),
    [completedQuestIdsList]
  );

  const treeData = useMemo(
    () => buildQuestTreeData(quests, completedQuestIds),
    [quests, completedQuestIds]
  );

  const handleActivate = (
    node: TreeNodeDefinition<QuestTreeNodeData>
  ): void => {
    navigation?.on_open_quest?.(node.data.quest.id);
  };

  const renderNode = (
    node: TreeNodeDefinition<QuestTreeNodeData>
  ): ReactNode => (
    <QuestTreeNode quest={node.data.quest} state={node.data.state} />
  );

  const renderListNode = (
    node: TreeNodeDefinition<QuestTreeNodeData>
  ): ReactNode => (
    <QuestTreeListNode quest={node.data.quest} state={node.data.state} />
  );

  return (
    <Tree<QuestTreeNodeData>
      nodes={treeData.nodes}
      branches={treeData.branches}
      render_node={renderNode}
      render_list_node={renderListNode}
      on_node_activate={navigation?.on_open_quest ? handleActivate : undefined}
      accessibility_label={accessibilityLabel}
      mobile_mode={mobileMode}
      available_empty_state={
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          No Quests are currently available.
        </p>
      }
    />
  );
};

export default QuestTree;
