import React, { ReactNode } from 'react';

import TreeScreenReaderSummaryProps from '../types/tree-screen-reader-summary-props';

/**
 * Concise `sr-only` structural summary of overall Tree scale: total nodes,
 * root count, and maximum hierarchy depth, alongside the domain-supplied
 * accessibility label. Never duplicates every node into a second focusable
 * hidden tree.
 */
const TreeScreenReaderSummary = ({
  accessibility_label: accessibilityLabel,
  total_nodes: totalNodes,
  root_count: rootCount,
  max_depth: maxDepth,
}: TreeScreenReaderSummaryProps): ReactNode => {
  const nodesLabel = totalNodes === 1 ? '1 node' : `${totalNodes} nodes`;
  const rootsLabel = rootCount === 1 ? '1 root' : `${rootCount} roots`;

  return (
    <p className="sr-only">
      {accessibilityLabel}. {nodesLabel}. {rootsLabel}. Maximum depth {maxDepth}
      .
    </p>
  );
};

export default TreeScreenReaderSummary;
