import React from 'react';

import CalculateClampCenterOffsetDefinition from 'ui/draggable/utils/definitions/calculate-clamp-center-offset-definition';

export const calculateClamCentreOffset = (
  containerRef: React.RefObject<HTMLDivElement | null>,
  contentRef: React.RefObject<HTMLDivElement | null>,
  center_on_x: number,
  center_on_y: number
): CalculateClampCenterOffsetDefinition => {
  if (!containerRef.current || !contentRef.current) {
    return {
      clamped_x: 0,
      clamped_y: 0,
    };
  }

  const { width: viewPortWidth, height: viewPortHeight } =
    containerRef.current.getBoundingClientRect();

  const { width: contentWidth, height: contentHeight } =
    contentRef.current.getBoundingClientRect();

  const targetX = viewPortWidth / 2 - center_on_x;
  const targetY = viewPortHeight / 2 - center_on_y;

  const minX = viewPortWidth - contentWidth;
  const minY = viewPortHeight - contentHeight;
  const clampedX = Math.max(Math.min(targetX, 0), minX);
  const clampedY = Math.max(Math.min(targetY, 0), minY);

  return {
    clamped_x: clampedX,
    clamped_y: clampedY,
  };
};
