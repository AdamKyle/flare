import React, { ReactNode } from 'react';

import {
  QUEST_TREE_STATE_ICON,
  QUEST_TREE_STATE_LABELS,
  QUEST_TREE_STATE_SHORT_LABELS,
} from '../enums/quest-tree-state';
import {
  questCardBaseStyles,
  questCardBorderStyles,
  questCardPrimaryTextStyles,
  questCardSecondaryTextStyles,
  questCardThemeStyles,
} from '../styles/quest-card-styles';
import QuestTreeNodeProps from '../types/quest-tree-node-props';

/**
 * Presentational-only Quest list-node content for the generic Tree's
 * `ONLY_WHATS_AVAILABLE` mobile fallback (`render_list_node`). Shares
 * `QuestCard`'s established visual language but renders no button of its
 * own — the generic `TreeList` owns the single interactive activation
 * target, so this component must never nest a second button inside it.
 */
const QuestTreeListNode = ({ quest, state }: QuestTreeNodeProps): ReactNode => {
  const npcName = quest.npc?.name ?? null;
  const childCount = quest.children.length;
  const hasMeta = Boolean(npcName) || childCount > 0;

  return (
    <div
      className={`${questCardBaseStyles()} ${questCardThemeStyles()} ${questCardBorderStyles(state)}`}
    >
      <i
        className={`ra ra-scroll-unfurled text-2xl ${questCardSecondaryTextStyles()}`}
        aria-hidden="true"
      />
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <span
          className={`text-sm font-semibold break-words ${questCardPrimaryTextStyles()}`}
        >
          {quest.name}
        </span>
        <span
          className={`flex items-center gap-1 text-xs font-medium ${questCardSecondaryTextStyles()}`}
        >
          <span aria-hidden="true">{QUEST_TREE_STATE_ICON[state]}</span>
          <span>{QUEST_TREE_STATE_SHORT_LABELS[state]}</span>
          <span className="sr-only">{QUEST_TREE_STATE_LABELS[state]}</span>
        </span>
        {hasMeta && (
          <div
            className={`flex flex-col gap-0.5 text-xs ${questCardSecondaryTextStyles()}`}
          >
            {npcName && <span>Quest Giver: {npcName}</span>}
            {childCount > 0 && (
              <span>
                {childCount} {childCount === 1 ? 'child Quest' : 'child Quests'}
              </span>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default QuestTreeListNode;
