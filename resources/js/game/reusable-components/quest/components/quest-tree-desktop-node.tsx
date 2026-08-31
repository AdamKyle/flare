import React, { ReactNode } from 'react';

import {
  QUEST_TREE_STATE_ICON,
  QUEST_TREE_STATE_LABELS,
  QUEST_TREE_STATE_SHORT_LABELS,
  QuestTreeState,
} from '../enums/quest-tree-state';
import QuestTreeDesktopNodeProps from '../types/quest-tree-desktop-node-props';
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

const CONNECTOR_CLASSES = 'bg-danube-300 dark:bg-danube-700';

/**
 * Desktop/tablet Quest tree node: the node is centered above its own
 * children, which are laid out horizontally beneath it with connector
 * lines indicating hierarchy. Recursion builds a real branching tree
 * rather than an indented list. Each child wrapper is `flex-1`/`min-w-0`,
 * so every subtree owns only a proportional share of its parent's
 * available width — the tree always fits the content region and never
 * needs horizontal scrolling; Quest names wrap instead. Connector lines
 * are decorative (`aria-hidden`); `role="treeitem"`/`aria-level`/
 * `aria-expanded` on each node and `role="group"` on a node's child region
 * carry the real accessible hierarchy.
 */
const QuestTreeDesktopNode = ({
  quest,
  depth,
  completed_quest_ids: completedQuestIds,
  focused_id: focusedId,
  on_select: onSelect,
  on_focus_node: onFocusNode,
  node_refs: nodeRefs,
}: QuestTreeDesktopNodeProps): ReactNode => {
  const state = resolveQuestTreeState(quest, completedQuestIds);
  const hasChildren = quest.children.length > 0;
  const isFocused = focusedId === quest.id;

  const handleActivate = (): void => {
    onSelect(quest.id);
  };

  const renderNodeCard = (): ReactNode => (
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
      aria-expanded={hasChildren ? true : undefined}
      tabIndex={isFocused ? 0 : -1}
      onFocus={() => onFocusNode(quest.id)}
      onClick={handleActivate}
      onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handleActivate();
        }
      }}
      className="border-glacier-200 dark:border-glacier-800 bg-glacier-0 focus-visible:ring-danube-400 hover:border-danube-300 dark:hover:border-danube-700 flex max-w-full cursor-pointer flex-col items-center gap-1 rounded-md border px-2 py-1.5 text-center shadow-sm focus:outline-none focus-visible:ring-2 dark:bg-gray-900"
    >
      <span
        aria-hidden="true"
        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATE_BADGE_CLASSES[state]}`}
      >
        {QUEST_TREE_STATE_ICON[state]} {QUEST_TREE_STATE_SHORT_LABELS[state]}
      </span>
      <span className="text-sm font-medium break-words text-gray-900 dark:text-gray-100">
        {quest.name}
      </span>
      <span className="sr-only">{QUEST_TREE_STATE_LABELS[state]}</span>
    </div>
  );

  const renderChildWrapper = (
    child: QuestTreeDesktopNodeProps['quest'],
    index: number,
    total: number
  ): ReactNode => (
    <div
      key={child.id}
      className="relative flex min-w-0 flex-1 flex-col items-center px-1 sm:px-2"
    >
      <div
        aria-hidden="true"
        className={`absolute top-0 left-0 h-px w-1/2 ${CONNECTOR_CLASSES} ${index === 0 ? 'invisible' : ''}`}
      />
      <div
        aria-hidden="true"
        className={`absolute top-0 right-0 h-px w-1/2 ${CONNECTOR_CLASSES} ${index === total - 1 ? 'invisible' : ''}`}
      />
      <div aria-hidden="true" className={`h-4 w-px ${CONNECTOR_CLASSES}`} />
      <QuestTreeDesktopNode
        quest={child}
        depth={depth + 1}
        completed_quest_ids={completedQuestIds}
        focused_id={focusedId}
        on_select={onSelect}
        on_focus_node={onFocusNode}
        node_refs={nodeRefs}
      />
    </div>
  );

  const renderChildren = (): ReactNode => {
    if (!hasChildren) {
      return null;
    }

    return (
      <div className="flex w-full min-w-0 flex-col items-center">
        <div aria-hidden="true" className={`h-4 w-px ${CONNECTOR_CLASSES}`} />
        <div role="group" className="flex w-full min-w-0 items-start">
          {quest.children.map((child, index) =>
            renderChildWrapper(child, index, quest.children.length)
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="flex w-full min-w-0 flex-col items-center">
      {renderNodeCard()}
      {renderChildren()}
    </div>
  );
};

export default QuestTreeDesktopNode;
