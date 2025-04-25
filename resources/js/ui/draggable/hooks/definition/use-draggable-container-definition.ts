import React from 'react';

export default interface UseDraggableContainerDefinition {
  containerRef: React.RefObject<HTMLDivElement | null>;
  contentRef: React.RefObject<HTMLDivElement | null>;
  position: { x: number; y: number };
  onMouseDown: (e: React.MouseEvent) => void;
  onTouchStart: (e: React.TouchEvent) => void;
  onKeyDown: (e: React.KeyboardEvent<HTMLDivElement>) => void;
}
