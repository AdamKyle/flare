import React, { ReactNode } from 'react';

import TreeNodeDefinition from '../definitions/tree-node-definition';
import TreeListProps from '../types/tree-list-props';

/**
 * Generic mobile `ONLY_WHATS_AVAILABLE` fallback: a semantic `<ul>`/`<li>`
 * list of only the currently available Tree nodes, with no connector lines,
 * indentation, or fake ARIA tree semantics. The generic Tree always owns
 * activation here: `render_list_node` (or, when absent, `render_node`) must
 * supply presentational content only, and `TreeList` wraps that content in
 * the single real `<button type="button">` when `on_node_activate` exists,
 * so a presentational renderer is never required to build its own
 * interactive control and a nested-button conflict can never occur.
 */
const TreeList = <TData,>({
  nodes,
  render_node: renderNode,
  render_list_node: renderListNode,
  on_node_activate: onNodeActivate,
  available_empty_state: availableEmptyState,
}: TreeListProps<TData>): ReactNode => {
  const availableNodes = nodes.filter((node) => node.is_available);

  const renderItem = (node: TreeNodeDefinition<TData>): ReactNode => {
    const content = renderListNode ? renderListNode(node) : renderNode(node);

    if (!onNodeActivate) {
      return content;
    }

    return (
      <button
        type="button"
        onClick={() => onNodeActivate(node)}
        aria-label={node.accessibility_label}
        className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 w-full rounded-md text-left focus:outline-none focus-visible:ring-2"
      >
        {content}
      </button>
    );
  };

  if (availableNodes.length === 0) {
    return <>{availableEmptyState ?? null}</>;
  }

  return (
    <ul className="flex w-full min-w-0 flex-col gap-2">
      {availableNodes.map((node) => (
        <li key={node.id}>{renderItem(node)}</li>
      ))}
    </ul>
  );
};

export default TreeList;
