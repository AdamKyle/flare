import clsx from 'clsx';
import React from 'react';

import { useDraggableContainer } from 'ui/draggable/hooks/use-draggable-container';
import DraggableContainerWrapperProps from 'ui/draggable/types/draggable-container-wrapper-props';

const DraggableContainerWrapper = ({
  additional_css,
  children,
}: DraggableContainerWrapperProps) => {
  const { containerRef, contentRef, position, onMouseDown } =
    useDraggableContainer();

  return (
    <div
      ref={containerRef}
      className={clsx(
        'relative overflow-hidden touch-none select-none border border-gray-300 rounded',
        additional_css
      )}
    >
      <div
        ref={contentRef}
        onMouseDown={onMouseDown}
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
