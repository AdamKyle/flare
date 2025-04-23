import React from 'react';

import { useDraggableContainer } from 'ui/draggable/hooks/use-draggable-container';
import DraggableContainerWrapperProps from 'ui/draggable/types/draggable-container-wrapper-props';

const DraggableContainerWrapper = ({
  width,
  height,
  children,
}: DraggableContainerWrapperProps) => {
  const { containerRef, contentRef, position, onMouseDown } =
    useDraggableContainer();

  return (
    <div
      ref={containerRef}
      style={{ width, height }}
      className="relative overflow-hidden touch-none select-none border border-gray-300 rounded"
    >
      <div
        ref={contentRef}
        onMouseDown={onMouseDown}
        className="absolute cursor-grab active:cursor-grabbing"
        style={{
          transform: `translate(${position.x}px, ${position.y}px)`,
        }}
      >
        {children}
      </div>
    </div>
  );
};

export default DraggableContainerWrapper;
