import clsx from 'clsx';
import React, { useLayoutEffect } from 'react';

import { useDraggableContainer } from 'ui/draggable/hooks/use-draggable-container';
import DraggableContainerWrapperProps from 'ui/draggable/types/draggable-container-wrapper-props';
import { calculateClamCentreOffset } from 'ui/draggable/utils/calculate-clam-centre-offset';

const DraggableContainerWrapper = ({
  additional_css,
  children,
  center_on_x,
  center_on_y,
}: DraggableContainerWrapperProps) => {
  const {
    containerRef,
    contentRef,
    position,
    setPosition,
    onMouseDown,
    onTouchStart,
    onKeyDown,
  } = useDraggableContainer();

  useLayoutEffect(() => {
    const centerOffset = calculateClamCentreOffset(
      containerRef,
      contentRef,
      center_on_x,
      center_on_y
    );

    setPosition({ x: centerOffset.clamped_x, y: centerOffset.clamped_y });
  }, [containerRef, contentRef, center_on_x, center_on_y, setPosition]);

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
