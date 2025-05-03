import React from 'react';

import { StateSetter } from '../../../../types/state-setter-type';

export default interface UseDraggableContainerDefinition {
  containerRef: React.RefObject<HTMLDivElement | null>;
  contentRef: React.RefObject<HTMLDivElement | null>;
  position: { x: number; y: number };
  setPosition: StateSetter<{ x: number; y: number }>;
  onMouseDown: (e: React.MouseEvent) => void;
  onTouchStart: (e: React.TouchEvent) => void;
  onKeyDown: (e: React.KeyboardEvent<HTMLDivElement>) => void;
}
