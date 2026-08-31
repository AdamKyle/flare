import React, { ReactNode } from 'react';

import QuestNode from './quest-node';
import QuestTreeLayoutProps from '../types/quest-tree-layout-props';

/**
 * Mobile Quest tree: a vertical, expand/collapse hierarchy with no
 * horizontal canvas or panning required.
 */
const QuestTreeMobile = ({
  quests,
  completed_quest_ids: completedQuestIds,
  expanded_ids: expandedIds,
  focused_id: focusedId,
  on_toggle_expand: onToggleExpand,
  on_select: onSelect,
  on_focus_node: onFocusNode,
  node_refs: nodeRefs,
  on_key_down: onKeyDown,
}: QuestTreeLayoutProps): ReactNode => (
  <div className="md:hidden">
    <ul
      role="tree"
      aria-label="Quest tree"
      onKeyDown={onKeyDown}
      className="space-y-3"
    >
      {quests.map((quest) => (
        <QuestNode
          key={quest.id}
          quest={quest}
          depth={0}
          completed_quest_ids={completedQuestIds}
          expanded_ids={expandedIds}
          focused_id={focusedId}
          on_toggle_expand={onToggleExpand}
          on_select={onSelect}
          on_focus_node={onFocusNode}
          node_refs={nodeRefs}
          collapsible
        />
      ))}
    </ul>
  </div>
);

export default QuestTreeMobile;
