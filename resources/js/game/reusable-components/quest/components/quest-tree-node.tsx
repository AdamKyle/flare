import clsx from 'clsx';
import React, { ReactNode } from 'react';

import {
  QUEST_TREE_STATE_ICON,
  QUEST_TREE_STATE_SHORT_LABELS,
} from '../enums/quest-tree-state';
import {
  QUEST_TREE_STATE_TEXT_STYLES,
  QUEST_TREE_STATE_TREE_COLOR,
} from '../styles/quest-tree-state-styles';
import QuestTreeNodeProps from '../types/quest-tree-node-props';

import TREE_NODE_COLOR_STYLES from 'ui/tree/styles/tree-node-color-styles';

const QuestTreeNode = ({ quest, state }: QuestTreeNodeProps): ReactNode => {
  const interactiveForeground =
    TREE_NODE_COLOR_STYLES[QUEST_TREE_STATE_TREE_COLOR[state]]
      .interactive_fill_foreground;

  return (
    <div className="flex h-full w-full flex-col items-center justify-center gap-1 px-3 py-2 text-center">
      <span
        className={clsx(
          'inline-flex items-center gap-1 text-xs font-medium transition-colors',
          QUEST_TREE_STATE_TEXT_STYLES[state],
          interactiveForeground
        )}
      >
        <span aria-hidden="true">{QUEST_TREE_STATE_ICON[state]}</span>
        <span aria-hidden="true">{QUEST_TREE_STATE_SHORT_LABELS[state]}</span>
      </span>
      <span
        className={clsx(
          'text-glacier-900 dark:text-glacier-100 text-sm font-semibold break-words transition-colors',
          interactiveForeground
        )}
      >
        {quest.name}
      </span>
      {quest.npc?.name && (
        <span
          className={clsx(
            'text-glacier-700 dark:text-glacier-300 text-xs break-words transition-colors',
            interactiveForeground
          )}
        >
          Quest Giver: {quest.npc.name}
        </span>
      )}
    </div>
  );
};

export default QuestTreeNode;
