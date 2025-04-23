import { useEffect, useRef, useState } from 'react';

import UseDraggableContainerDefinition from 'ui/draggable/hooks/definition/use-draggable-container-definition';

/**
 * A hook that enables dragging of any content inside a container using mouse interaction.
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
      if (!dragging || !start) return;

      setPosition((prev) => {
        const newX = prev.x + e.clientX - start.x;
        const newY = prev.y + e.clientY - start.y;
        setStart({ x: e.clientX, y: e.clientY });
        return { x: newX, y: newY };
      });
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
  }, [dragging, start]);

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
