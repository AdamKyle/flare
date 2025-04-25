import React, { useEffect, useRef, useState } from 'react';

import UseDraggableContainerDefinition from 'ui/draggable/hooks/definition/use-draggable-container-definition';

/**
 * A hook that enables dragging of any content inside a container using mouse and touch interaction,
 * with enforced bounds so the draggable content can't escape the visible container.
 *
 * @returns An object containing:
 * - `containerRef`: Ref to attach to the outer container (the visible viewport).
 * - `contentRef`: Ref to attach to the content being dragged (the full map).
 * - `position`: The current x and y offset of the content.
 * - `onMouseDown`: The mousedown handler to initiate dragging via mouse.
 * - `onTouchStart`: The touchstart handler to initiate dragging via touch devices.
 */
export const useDraggableContainer = (): UseDraggableContainerDefinition => {
  const containerRef = useRef<HTMLDivElement>(null);
  const contentRef = useRef<HTMLDivElement>(null);

  const [position, setPosition] = useState({ x: 0, y: 0 });
  const [dragging, setDragging] = useState(false);
  const [start, setStart] = useState<{ x: number; y: number } | null>(null);

  const clampPosition = (x: number, y: number): { x: number; y: number } => {
    if (!containerRef.current || !contentRef.current) return { x, y };

    const containerRect = containerRef.current.getBoundingClientRect();
    const contentRect = contentRef.current.getBoundingClientRect();

    const minX = containerRect.width - contentRect.width;
    const minY = containerRect.height - contentRect.height;

    return {
      x: Math.min(0, Math.max(minX, x)),
      y: Math.min(0, Math.max(minY, y)),
    };
  };

  useEffect(() => {
    const handlePointerMove = (clientX: number, clientY: number) => {
      if (!dragging || !start) return;

      const deltaX = clientX - start.x;
      const deltaY = clientY - start.y;

      const newX = position.x + deltaX;
      const newY = position.y + deltaY;

      const clamped = clampPosition(newX, newY);

      setPosition(clamped);
      setStart({ x: clientX, y: clientY });
    };

    const onMouseMove = (e: MouseEvent) =>
      handlePointerMove(e.clientX, e.clientY);
    const onTouchMove = (e: TouchEvent) => {
      const touch = e.touches[0];
      if (touch) handlePointerMove(touch.clientX, touch.clientY);
    };

    const stopDragging = () => setDragging(false);

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', stopDragging);
    window.addEventListener('touchmove', onTouchMove, { passive: false });
    window.addEventListener('touchend', stopDragging);

    return () => {
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', stopDragging);
      window.removeEventListener('touchmove', onTouchMove);
      window.removeEventListener('touchend', stopDragging);
    };
  }, [dragging, start, position]);

  const onMouseDown = (e: React.MouseEvent) => {
    setDragging(true);
    setStart({ x: e.clientX, y: e.clientY });
  };

  const onTouchStart = (e: React.TouchEvent) => {
    const touch = e.touches[0];
    if (touch) {
      setDragging(true);
      setStart({ x: touch.clientX, y: touch.clientY });
    }
  };

  const onKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
    const move = 50;
    let newX = position.x;
    let newY = position.y;

    if (e.key === 'ArrowUp') {
      newY += move;
    } else if (e.key === 'ArrowDown') {
      newY -= move;
    } else if (e.key === 'ArrowLeft') {
      newX += move;
    } else if (e.key === 'ArrowRight') {
      newX -= move;
    } else {
      return; // ignore other keys
    }

    const clamped = clampPosition(newX, newY);
    setPosition(clamped);
  };

  return {
    containerRef,
    contentRef,
    position,
    onMouseDown,
    onTouchStart,
    onKeyDown,
  };
};
