import { Handle, Node, NodeProps, Position, useReactFlow } from '@xyflow/react';
import clsx from 'clsx';
import { useReducedMotion } from 'framer-motion';
import React, { ReactNode, useRef } from 'react';

import TreeFlowNodeDataDefinition from '../definitions/tree-flow-node-data-definition';
import TREE_NODE_COLOR_STYLES from '../styles/tree-node-color-styles';

const HANDLE_HIDDEN_CLASSES =
  '!h-0 !min-h-0 !w-0 !min-w-0 !border-0 !bg-transparent opacity-0 pointer-events-none';
const SAFE_VISIBILITY_MARGIN_PX = 16;

/**
 * Generic React Flow custom node. Renders domain content supplied by
 * `render_node` inside a real interactive button when the domain requested
 * activation, or a semantically meaningful noninteractive `<article>`
 * otherwise. Owns only generic Tree presentation: `TreeColor` border/focus
 * treatment, neutral Flare light/dark surface, the resolved structural
 * accessible name, and bringing a keyboard-focused node into the visible
 * React Flow viewport without moving the Tree for an ordinary pointer/touch
 * activation. `nodrag nopan` on the rendered content prevents an activation
 * click/tap from also starting a node-drag or pane-pan gesture. Never
 * renders domain-specific content itself.
 */
const TreeNode = ({
  data,
}: NodeProps<Node<TreeFlowNodeDataDefinition>>): ReactNode => {
  const reactFlowInstance = useReactFlow();
  const reduceMotion = useReducedMotion();
  const colorStyles = TREE_NODE_COLOR_STYLES[data.color];
  const isPointerOriginFocusRef = useRef(false);

  const handlePointerDown = (): void => {
    isPointerOriginFocusRef.current = true;
  };

  const resetPointerOriginFocus = (): void => {
    isPointerOriginFocusRef.current = false;
  };

  const handleFocus = (event: React.FocusEvent<HTMLElement>): void => {
    if (isPointerOriginFocusRef.current) {
      isPointerOriginFocusRef.current = false;

      return;
    }

    const flowElement = event.currentTarget.closest('.react-flow');

    if (!(flowElement instanceof HTMLElement)) {
      return;
    }

    const nodeRect = event.currentTarget.getBoundingClientRect();
    const flowRect = flowElement.getBoundingClientRect();

    let deltaX = 0;
    let deltaY = 0;

    if (nodeRect.left < flowRect.left + SAFE_VISIBILITY_MARGIN_PX) {
      deltaX = flowRect.left + SAFE_VISIBILITY_MARGIN_PX - nodeRect.left;
    } else if (nodeRect.right > flowRect.right - SAFE_VISIBILITY_MARGIN_PX) {
      deltaX = flowRect.right - SAFE_VISIBILITY_MARGIN_PX - nodeRect.right;
    }

    if (nodeRect.top < flowRect.top + SAFE_VISIBILITY_MARGIN_PX) {
      deltaY = flowRect.top + SAFE_VISIBILITY_MARGIN_PX - nodeRect.top;
    } else if (nodeRect.bottom > flowRect.bottom - SAFE_VISIBILITY_MARGIN_PX) {
      deltaY = flowRect.bottom - SAFE_VISIBILITY_MARGIN_PX - nodeRect.bottom;
    }

    if (deltaX === 0 && deltaY === 0) {
      return;
    }

    const currentViewport = reactFlowInstance.getViewport();

    void reactFlowInstance.setViewport(
      {
        x: currentViewport.x + deltaX,
        y: currentViewport.y + deltaY,
        zoom: currentViewport.zoom,
      },
      { duration: reduceMotion ? 0 : 300 }
    );
  };

  const renderContent = (): ReactNode => {
    if (data.on_activate) {
      return (
        <button
          type="button"
          onClick={data.on_activate}
          onPointerDown={handlePointerDown}
          onPointerUp={resetPointerOriginFocus}
          onPointerCancel={resetPointerOriginFocus}
          onPointerLeave={resetPointerOriginFocus}
          onBlur={resetPointerOriginFocus}
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
