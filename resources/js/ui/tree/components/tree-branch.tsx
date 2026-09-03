import { BaseEdge, Edge, EdgeProps, getSmoothStepPath } from '@xyflow/react';
import clsx from 'clsx';
import React, { ReactNode } from 'react';

import TreeBranchDataDefinition from '../definitions/tree-branch-data-definition';
import TreeColor from '../enums/tree-color';
import TREE_BRANCH_COLOR_STYLES from '../styles/tree-branch-color-styles';

/**
 * Generic React Flow custom edge: a decorative, non-interactive
 * top-to-bottom hierarchical connector between a parent and child Tree
 * node. Uses a right-angle smooth-step path rather than a freeform
 * graph-editor curve, and the resolved `TreeColor` stroke.
 */
const TreeBranch = ({
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  data,
}: EdgeProps<Edge<TreeBranchDataDefinition>>): ReactNode => {
  const [path] = getSmoothStepPath({
    sourceX,
    sourceY,
    sourcePosition,
    targetX,
    targetY,
    targetPosition,
    borderRadius: 8,
  });

  const strokeClassName =
    TREE_BRANCH_COLOR_STYLES[data?.color ?? TreeColor.DANUBE];

  return (
    <BaseEdge
      path={path}
      className={clsx('fill-none stroke-2', strokeClassName)}
      aria-hidden="true"
    />
  );
};

export default TreeBranch;
