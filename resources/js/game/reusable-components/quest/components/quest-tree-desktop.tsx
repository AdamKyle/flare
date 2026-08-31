import React, { ReactNode } from 'react';

import QuestNode from './quest-node';
import QuestTreeLayoutProps from '../types/quest-tree-layout-props';

/**
 * Desktop/tablet Quest tree: a fully expanded branching hierarchy with
 * connector lines. The tree region owns its own horizontal/vertical
 * overflow so the page itself never scrolls horizontally.
 */
const QuestTreeDesktop = ({
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
  <div className="hidden overflow-x-auto md:block">
    <ul
      role="tree"
      aria-label="Quest tree"
      onKeyDown={onKeyDown}
      className="min-w-max"
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
          collapsible={false}
        />
      ))}
    </ul>
  </div>
);

export default QuestTreeDesktop;
