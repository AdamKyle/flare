import React, { ReactNode } from 'react';

import QuestTreeDesktopNode from './quest-tree-desktop-node';
import QuestTreeLayoutProps from '../types/quest-tree-layout-props';

/**
 * Desktop/tablet Quest tree: a real branching hierarchy — each root Quest
 * centered above a connected two-column major-branch grid, joined by a
 * single central vertical spine, with every deeper generation growing
 * purely vertically inside its own branch lane. Multiple roots render as
 * separate full-width tree sections stacked vertically. Branch width is a
 * bounded Tailwind token (`max-w-sm`, never `flexGrow` sibling weighting or
 * JS measurement), so a dense branch simply grows the page taller instead
 * of squeezing its siblings; the tree never scrolls or overflows
 * horizontally.
 */
const QuestTreeDesktop = ({
  quests,
  completed_quest_ids: completedQuestIds,
  focused_id: focusedId,
  on_select: onSelect,
  on_focus_node: onFocusNode,
  node_refs: nodeRefs,
  on_key_down: onKeyDown,
}: QuestTreeLayoutProps): ReactNode => {
  return (
    <div className="hidden w-full min-w-0 pt-6 pb-10 md:block">
      <ul
        role="tree"
        aria-label="Quest tree"
        onKeyDown={onKeyDown}
        className="flex w-full min-w-0 flex-col items-center space-y-12"
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
};

export default QuestTreeDesktop;
