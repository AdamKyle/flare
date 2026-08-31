import React, { ReactNode } from 'react';

import {
  QUEST_TREE_STATE_ICON,
  QUEST_TREE_STATE_LABELS,
  QUEST_TREE_STATE_SHORT_LABELS,
  QuestTreeState,
} from '../enums/quest-tree-state';
import QuestNodeProps from '../types/quest-node-props';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

const STATE_BADGE_CLASSES: Record<QuestTreeState, string> = {
  [QuestTreeState.COMPLETED]:
    'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
  [QuestTreeState.PARENT_LOCKED]:
    'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
  [QuestTreeState.PREREQUISITE_LOCKED]:
    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  [QuestTreeState.AVAILABLE]:
    'bg-danube-100 text-danube-800 dark:bg-danube-900/40 dark:text-danube-300',
};

const QuestNode = ({
  quest,
  depth,
  completed_quest_ids: completedQuestIds,
  expanded_ids: expandedIds,
  focused_id: focusedId,
  on_toggle_expand: onToggleExpand,
  on_select: onSelect,
  on_focus_node: onFocusNode,
  node_refs: nodeRefs,
  collapsible,
}: QuestNodeProps): ReactNode => {
  const state = resolveQuestTreeState(quest, completedQuestIds);
  const hasChildren = quest.children.length > 0;
  const isExpanded = !collapsible || expandedIds.has(quest.id);
  const isFocused = focusedId === quest.id;

  const resolveAriaExpanded = (): boolean | undefined => {
    if (!hasChildren) {
      return undefined;
    }

    if (!collapsible) {
      return true;
    }

    return isExpanded;
  };

  const handleActivate = (): void => {
    onSelect(quest.id);
  };

  const handleToggle = (event: React.MouseEvent): void => {
    event.stopPropagation();
    onToggleExpand(quest.id);
  };

  const renderExpandButton = (): ReactNode => {
    if (!hasChildren || !collapsible) {
      return null;
    }

    return (
      <button
        type="button"
        aria-label={`${isExpanded ? 'Collapse' : 'Expand'} ${quest.name}`}
        onClick={handleToggle}
        className="text-gray-500 dark:text-gray-400"
      >
        <span aria-hidden="true">{isExpanded ? '▾' : '▸'}</span>
      </button>
    );
  };

  const renderChildCount = (): ReactNode => {
    if (!hasChildren) {
      return null;
    }

    return (
      <span className="text-glacier-500 dark:text-glacier-500 text-xs">
        ({quest.children.length})
      </span>
    );
  };

  const renderChildren = (): ReactNode => {
    if (!hasChildren || !isExpanded) {
      return null;
    }

    return (
      <ul
        role="group"
        className="border-danube-200 dark:border-danube-800 bg-glacier-50/50 dark:bg-glacier-900/20 mt-2 ml-3 space-y-2 border-l pl-3"
      >
        {quest.children.map((child) => (
          <QuestNode
            key={child.id}
            quest={child}
            depth={depth + 1}
            completed_quest_ids={completedQuestIds}
            expanded_ids={expandedIds}
            focused_id={focusedId}
            on_toggle_expand={onToggleExpand}
            on_select={onSelect}
            on_focus_node={onFocusNode}
            node_refs={nodeRefs}
            collapsible={collapsible}
          />
        ))}
      </ul>
    );
  };

  return (
    <li className="list-none">
      <div
        ref={(element) => {
          if (element) {
            nodeRefs.current.set(quest.id, element);
          } else {
            nodeRefs.current.delete(quest.id);
          }
        }}
        role="treeitem"
        aria-level={depth + 1}
        aria-expanded={resolveAriaExpanded()}
        tabIndex={isFocused ? 0 : -1}
        onFocus={() => onFocusNode(quest.id)}
        onClick={handleActivate}
        onKeyDown={(event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            handleActivate();
          }
        }}
        className="focus-visible:ring-danube-400 flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 dark:hover:bg-gray-800"
      >
        {renderExpandButton()}

        <span
          aria-hidden="true"
          className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATE_BADGE_CLASSES[state]}`}
        >
          {QUEST_TREE_STATE_ICON[state]}
        </span>

        <span className="font-medium text-gray-900 dark:text-gray-100">
          {quest.name}
        </span>

        {renderChildCount()}

        <span className="sr-only">{QUEST_TREE_STATE_LABELS[state]}</span>

        <span
          aria-hidden="true"
          className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${STATE_BADGE_CLASSES[state]}`}
        >
          {QUEST_TREE_STATE_SHORT_LABELS[state]}
        </span>
      </div>

      {renderChildren()}
    </li>
  );
};

export default QuestNode;
