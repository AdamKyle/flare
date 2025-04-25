import clsx from 'clsx';
import React from 'react';

import { useDraggableContainer } from 'ui/draggable/hooks/use-draggable-container';
import DraggableContainerWrapperProps from 'ui/draggable/types/draggable-container-wrapper-props';

const DraggableContainerWrapper = ({
  additional_css,
  children,
}: DraggableContainerWrapperProps) => {
  const {
    containerRef,
    contentRef,
    position,
    onMouseDown,
    onTouchStart,
    onKeyDown,
  } = useDraggableContainer();

  return (
    <div
      ref={containerRef}
      role="region"
      aria-label="Draggable map region"
      tabIndex={0}
      onKeyDown={onKeyDown}
      className={clsx(
        'relative overflow-hidden select-none border border-gray-300 rounded',
        'focus:outline-none focus-visible:ring focus-visible:ring-blue-500 focus-visible:ring-offset-2',
        additional_css
      )}
    >
      <div className="sr-only" aria-live="polite">
        Map position: {position.x}px horizontal, {position.y}px vertical
      </div>

      <div
        ref={contentRef}
        onMouseDown={onMouseDown}
        onTouchStart={onTouchStart}
        className="absolute cursor-grab active:cursor-grabbing"
        style={{
          transform: `translate(${Math.round(position.x)}px, ${Math.round(position.y)}px)`,
        }}
      >
        {children}
      </div>
    </div>
  );
};

export default DraggableContainerWrapper;
