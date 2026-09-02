import clsx from 'clsx';
import React, { ReactNode } from 'react';

import {
  QUEST_TREE_STATE_ICON,
  QUEST_TREE_STATE_LABELS,
  QUEST_TREE_STATE_SHORT_LABELS,
} from '../enums/quest-tree-state';
import {
  QUEST_TREE_NODE_STATE_TEXT_STYLES,
  QUEST_TREE_STATE_BORDER_STYLES,
  resolveQuestTreeChildrenSpacingClass,
  resolveQuestTreeNodeLevelStyles,
} from '../styles/quest-tree-node-styles';
import QuestTreeDesktopNodeProps from '../types/quest-tree-desktop-node-props';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

const CONNECTOR_CLASSES = 'bg-glacier-500/70 dark:bg-glacier-600/70';
const CONNECTOR_BORDER_CLASSES =
  'border-glacier-500/70 dark:border-glacier-600/70';

/**
 * Desktop/tablet Quest tree node: the root centers above a connected
 * two-column major-branch grid (`grid-cols-2`), with a single central
 * vertical spine running through every major-branch row. Root children
 * alternate left/right columns in API order (0/1, 2/3, ...), each
 * connected to the spine by a short horizontal connector; an odd final
 * branch spans both columns, centered, and connects to the spine with its
 * own vertical stem. Every generation below a major branch renders
 * vertically inside that branch's own lane (`max-w-sm`) with a left
 * connector rail, so dense nested branches grow the page taller instead of
 * collapsing into unreadably thin columns; the tree never scrolls or
 * overflows horizontally. Connector lines are decorative (`aria-hidden`)
 * and sit behind the cards; this node's own
 * `role="treeitem"`/`aria-level`/`aria-expanded` wrapper directly contains
 * its `role="group"` child region as a real DOM descendant, and
 * click/keydown/focus handlers stop propagation so a descendant's
 * activation/focus is never re-reported by an ancestor node.
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
  const levelStyles = resolveQuestTreeNodeLevelStyles(depth);

  const handleActivate = (): void => {
    onSelect(quest.id);
  };

  const handleClick = (event: React.MouseEvent): void => {
    event.stopPropagation();
    handleActivate();
  };

  const handleKeyDown = (event: React.KeyboardEvent): void => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      event.stopPropagation();
      handleActivate();
    }
  };

  const handleFocus = (event: React.FocusEvent): void => {
    event.stopPropagation();
    onFocusNode(quest.id);
  };

  const renderNodeCard = (): ReactNode => (
    <div
      className={clsx(
        'group-focus-visible:ring-glacier-500 flex w-full min-w-0 flex-col items-center gap-1 rounded-md border-2 text-center whitespace-normal transition-colors group-focus-visible:ring-2',
        QUEST_TREE_STATE_BORDER_STYLES[state],
        levelStyles.card,
        depth === 0 && 'mx-auto'
      )}
    >
      <span
        className={clsx(
          'inline-flex items-center gap-1 text-xs font-medium',
          QUEST_TREE_NODE_STATE_TEXT_STYLES[state]
        )}
      >
        <span aria-hidden="true">{QUEST_TREE_STATE_ICON[state]}</span>
        <span aria-hidden="true">{QUEST_TREE_STATE_SHORT_LABELS[state]}</span>
      </span>
      <span className={clsx('text-glacier-900 break-words', levelStyles.name)}>
        {quest.name}
      </span>
      <span className="sr-only">{QUEST_TREE_STATE_LABELS[state]}</span>
    </div>
  );

  const renderMajorBranchLane = (
    child: QuestTreeDesktopNodeProps['quest']
  ): ReactNode => (
    <QuestTreeDesktopNode
      quest={child}
      depth={depth + 1}
      completed_quest_ids={completedQuestIds}
      focused_id={focusedId}
      on_select={onSelect}
      on_focus_node={onFocusNode}
      node_refs={nodeRefs}
    />
  );

  const renderLeftMajorBranch = (
    child: QuestTreeDesktopNodeProps['quest']
  ): ReactNode => (
    <div key={child.id} className="relative flex min-w-0 justify-end pr-6">
      <div
        aria-hidden="true"
        className={clsx('absolute top-5 right-0 h-px w-6', CONNECTOR_CLASSES)}
      />
      <div
        aria-hidden="true"
        className={clsx(
          'absolute top-5 -right-4 h-px w-4 xl:-right-6 xl:w-6',
          CONNECTOR_CLASSES
        )}
      />
      <div className="w-full max-w-sm min-w-0">
        {renderMajorBranchLane(child)}
      </div>
    </div>
  );

  const renderRightMajorBranch = (
    child: QuestTreeDesktopNodeProps['quest']
  ): ReactNode => (
    <div key={child.id} className="relative flex min-w-0 justify-start pl-6">
      <div
        aria-hidden="true"
        className={clsx('absolute top-5 left-0 h-px w-6', CONNECTOR_CLASSES)}
      />
      <div
        aria-hidden="true"
        className={clsx(
          'absolute top-5 -left-4 h-px w-4 xl:-left-6 xl:w-6',
          CONNECTOR_CLASSES
        )}
      />
      <div className="w-full max-w-sm min-w-0">
        {renderMajorBranchLane(child)}
      </div>
    </div>
  );

  const renderCenteredMajorBranch = (
    child: QuestTreeDesktopNodeProps['quest']
  ): ReactNode => (
    <div
      key={child.id}
      className="col-span-2 flex w-full min-w-0 flex-col items-center"
    >
      <div aria-hidden="true" className={clsx('h-6 w-px', CONNECTOR_CLASSES)} />
      <div className="w-full max-w-sm min-w-0">
        {renderMajorBranchLane(child)}
      </div>
    </div>
  );

  const renderMajorBranch = (
    child: QuestTreeDesktopNodeProps['quest'],
    index: number,
    totalBranches: number
  ): ReactNode => {
    const isOddTotal = totalBranches % 2 === 1;
    const isLastOddBranch = isOddTotal && index === totalBranches - 1;

    if (isLastOddBranch) {
      return renderCenteredMajorBranch(child);
    }

    return index % 2 === 0
      ? renderLeftMajorBranch(child)
      : renderRightMajorBranch(child);
  };

  const renderMajorBranches = (): ReactNode => {
    const totalBranches = quest.children.length;

    return (
      <div
        className={clsx(
          'flex w-full min-w-0 flex-col items-center',
          resolveQuestTreeChildrenSpacingClass(depth)
        )}
      >
        <div
          aria-hidden="true"
          className={clsx('h-6 w-px', CONNECTOR_CLASSES)}
        />
        <div
          role="group"
          className="relative grid w-full min-w-0 grid-cols-2 gap-x-8 gap-y-10 xl:gap-x-12"
        >
          <div
            aria-hidden="true"
            className={clsx(
              'absolute top-0 left-1/2 -z-10 h-full w-px -translate-x-1/2',
              CONNECTOR_CLASSES
            )}
          />
          {quest.children.map((child, index) =>
            renderMajorBranch(child, index, totalBranches)
          )}
        </div>
      </div>
    );
  };

  const renderNestedChildWrapper = (
    child: QuestTreeDesktopNodeProps['quest']
  ): ReactNode => (
    <div key={child.id} className="relative w-full min-w-0">
      <div
        aria-hidden="true"
        className={clsx('absolute top-5 -left-4 h-px w-4', CONNECTOR_CLASSES)}
      />
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

  const renderNestedChildren = (): ReactNode => (
    <div
      className={clsx(
        'mt-4 w-full min-w-0 border-l-2 pl-4',
        CONNECTOR_BORDER_CLASSES
      )}
    >
      <div role="group" className="flex w-full min-w-0 flex-col gap-4">
        {quest.children.map(renderNestedChildWrapper)}
      </div>
    </div>
  );

  const renderChildren = (): ReactNode => {
    if (!hasChildren) {
      return null;
    }

    return depth === 0 ? renderMajorBranches() : renderNestedChildren();
  };

  return (
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
      onFocus={handleFocus}
      onClick={handleClick}
      onKeyDown={handleKeyDown}
      className={clsx(
        'group flex w-full min-w-0 cursor-pointer flex-col rounded-md focus:outline-none',
        depth === 0 ? 'items-center' : 'items-start'
      )}
    >
      {renderNodeCard()}
      {renderChildren()}
    </div>
  );
};

export default QuestTreeDesktopNode;
