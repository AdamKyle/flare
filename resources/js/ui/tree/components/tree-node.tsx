import { Handle, Node, NodeProps, Position, useReactFlow } from '@xyflow/react';
import clsx from 'clsx';
import { useReducedMotion } from 'framer-motion';
import React, { ReactNode } from 'react';

import TreeFlowNodeDataDefinition from '../definitions/tree-flow-node-data-definition';
import TREE_NODE_COLOR_STYLES from '../styles/tree-node-color-styles';

const HANDLE_HIDDEN_CLASSES =
  '!h-0 !min-h-0 !w-0 !min-w-0 !border-0 !bg-transparent opacity-0 pointer-events-none';

/**
 * Generic React Flow custom node. Renders domain content supplied by
 * `render_node` inside a real interactive button when the domain requested
 * activation, or a semantically meaningful noninteractive `<article>`
 * otherwise. Owns only generic Tree presentation: `TreeColor` border/focus
 * treatment, neutral Flare light/dark surface, the resolved structural
 * accessible name, and bringing a focused node into the React Flow
 * viewport. `nodrag nopan` on the rendered content prevents an activation
 * click/tap from also starting a node-drag or pane-pan gesture. Never
 * renders domain-specific content itself.
 */
const TreeNode = ({
  data,
  positionAbsoluteX,
  positionAbsoluteY,
  width,
  height,
}: NodeProps<Node<TreeFlowNodeDataDefinition>>): ReactNode => {
  const reactFlowInstance = useReactFlow();
  const reduceMotion = useReducedMotion();
  const colorStyles = TREE_NODE_COLOR_STYLES[data.color];

  const handleFocus = (): void => {
    const nodeWidth = width ?? 0;
    const nodeHeight = height ?? 0;

    void reactFlowInstance.setCenter(
      positionAbsoluteX + nodeWidth / 2,
      positionAbsoluteY + nodeHeight / 2,
      {
        zoom: reactFlowInstance.getZoom(),
        duration: reduceMotion ? 0 : 300,
      }
    );
  };

  const renderContent = (): ReactNode => {
    if (data.on_activate) {
      return (
        <button
          type="button"
          onClick={data.on_activate}
          onFocus={handleFocus}
          aria-label={data.accessible_label}
          className={clsx(
            'group nodrag nopan flex h-full w-full flex-col overflow-hidden rounded-md border-2 bg-white text-left shadow-sm transition-colors focus:outline-none focus-visible:ring-2 dark:bg-gray-800',
            colorStyles.border,
            colorStyles.focus_ring,
            colorStyles.interactive_fill_background
          )}
        >
          {data.content}
        </button>
      );
    }

    return (
      <article
        aria-label={data.accessible_label}
        onFocus={handleFocus}
        tabIndex={-1}
        className={clsx(
          'nodrag nopan flex h-full w-full flex-col overflow-hidden rounded-md border-2 bg-white shadow-sm dark:bg-gray-800',
          colorStyles.border
        )}
      >
        {data.content}
      </article>
    );
  };

  return (
    <>
      <Handle
        type="target"
        position={Position.Top}
        isConnectable={false}
        tabIndex={-1}
        aria-hidden="true"
        className={HANDLE_HIDDEN_CLASSES}
      />
      {renderContent()}
      <Handle
        type="source"
        position={Position.Bottom}
        isConnectable={false}
        tabIndex={-1}
        aria-hidden="true"
        className={HANDLE_HIDDEN_CLASSES}
      />
    </>
  );
};

export default TreeNode;
