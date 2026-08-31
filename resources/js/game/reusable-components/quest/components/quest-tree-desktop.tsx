import React, { ReactNode } from 'react';

import QuestTreeDesktopNode from './quest-tree-desktop-node';
import QuestTreeLayoutProps from '../types/quest-tree-layout-props';

/**
 * Desktop/tablet Quest tree: a real branching hierarchy — each root Quest
 * centered above its own children, recursively, with connector lines.
 * Multiple roots render as separate tree blocks. Each subtree owns only a
 * proportional share of the available width (recursive `flex-1`/`min-w-0`
 * distribution), so the tree always fits the content region; Quest names
 * wrap instead of forcing horizontal scrolling.
 */
const QuestTreeDesktop = ({
  quests,
  completed_quest_ids: completedQuestIds,
  focused_id: focusedId,
  on_select: onSelect,
  on_focus_node: onFocusNode,
  node_refs: nodeRefs,
  on_key_down: onKeyDown,
}: QuestTreeLayoutProps): ReactNode => (
  <div className="hidden w-full md:block">
    <ul
      role="tree"
      aria-label="Quest tree"
      onKeyDown={onKeyDown}
      className="flex w-full flex-col items-center gap-10"
    >
      {quests.map((quest) => (
        <li key={quest.id} className="w-full min-w-0 list-none">
          <QuestTreeDesktopNode
            quest={quest}
            depth={0}
            completed_quest_ids={completedQuestIds}
            focused_id={focusedId}
            on_select={onSelect}
            on_focus_node={onFocusNode}
            node_refs={nodeRefs}
          />
        </li>
      ))}
    </ul>
  </div>
);

export default QuestTreeDesktop;
