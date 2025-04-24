import { useEffect, useRef, useState } from 'react';

import UseDraggableContainerDefinition from 'ui/draggable/hooks/definition/use-draggable-container-definition';

/**
 * A hook that enables dragging of any content inside a container using mouse interaction,
 * with enforced bounds so the draggable content can't escape the visible container.
 *
 * @returns An object containing:
 * - `containerRef`: Ref to attach to the outer container.
 * - `contentRef`: Ref to attach to the content being dragged.
 * - `position`: The current x and y offset of the content.
 * - `onMouseDown`: The mousedown handler to initiate dragging.
 */
export function useDraggableContainer(): UseDraggableContainerDefinition {
  const containerRef = useRef<HTMLDivElement>(null);
  const contentRef = useRef<HTMLDivElement>(null);

  const [position, setPosition] = useState({ x: 0, y: 0 });
  const [dragging, setDragging] = useState(false);
  const [start, setStart] = useState<{ x: number; y: number } | null>(null);

  useEffect(() => {
    function onMouseMove(e: MouseEvent) {
      if (!dragging || !start || !containerRef.current || !contentRef.current)
        return;

      const containerRect = containerRef.current.getBoundingClientRect();
      const contentRect = contentRef.current.getBoundingClientRect();

      const deltaX = e.clientX - start.x;
      const deltaY = e.clientY - start.y;

      const newX = position.x + deltaX;
      const newY = position.y + deltaY;

      const minX = containerRect.width - contentRect.width;
      const minY = containerRect.height - contentRect.height;
      const clampedX = Math.min(0, Math.max(minX, newX));
      const clampedY = Math.min(0, Math.max(minY, newY));

      setPosition({ x: clampedX, y: clampedY });
      setStart({ x: e.clientX, y: e.clientY });
    }

    function onMouseUp() {
      setDragging(false);
    }

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);

    return () => {
      window.removeEventListener('mousemove', onMouseMove);
      window.removeEventListener('mouseup', onMouseUp);
    };
  }, [dragging, start, position]);

  function onMouseDown(e: React.MouseEvent) {
    setDragging(true);
    setStart({ x: e.clientX, y: e.clientY });
  }

  return {
    containerRef,
    contentRef,
    position,
    onMouseDown,
  };
}
