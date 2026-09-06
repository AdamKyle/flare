import { RefObject, useEffect } from 'react';

interface UseTreeDecorativeEdgeAccessibilityParams {
  container_ref: RefObject<HTMLDivElement | null>;
  edge_count: number;
}

/**
 * Hides React Flow's own generated `.react-flow__edges` SVG container from
 * assistive technology. This container is created internally by React Flow
 * rather than through the Tree's own `flowEdges`/`flowNodes` data, so it
 * cannot be marked presentational through `ariaRole`/`domAttributes`. Scoped
 * to the current Tree's own `container_ref` only; never queries outside it.
 */
export const useTreeDecorativeEdgeAccessibility = ({
  container_ref: containerRef,
  edge_count: edgeCount,
}: UseTreeDecorativeEdgeAccessibilityParams): void => {
  useEffect(() => {
    const container = containerRef.current;

    if (!container) {
      return;
    }

    const edgesContainer = container.querySelector('.react-flow__edges');

    if (!edgesContainer) {
      return;
    }

    edgesContainer.setAttribute('aria-hidden', 'true');
    edgesContainer.setAttribute('role', 'presentation');

    return () => {
      edgesContainer.removeAttribute('aria-hidden');
      edgesContainer.removeAttribute('role');
    };
  }, [containerRef, edgeCount]);
};
