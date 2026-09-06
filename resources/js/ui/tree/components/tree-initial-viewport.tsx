import { useReactFlow } from '@xyflow/react';
import { ReactNode, useEffect } from 'react';

import TreeInitialViewportProps from '../types/tree-initial-viewport-props';
import { resolveTreeDefaultViewport } from '../utils/resolve-tree-default-viewport';

/**
 * Positions the Tree viewport at the configured default zoom with the
 * resolved default focal node/root visible near the upper-central portion
 * of the container, instead of relying on React Flow's automatic
 * whole-tree `fitView` (which crushes a large Tree, such as a tall Raid
 * hierarchy, to a tiny overview on first render). Uses the same
 * `resolveTreeDefaultViewport` calculation as the Reset View control, so
 * both always agree. Re-centers whenever the resolved default focus
 * changes, so a newly selected Plane/Raid tab or a resolved
 * `default_focus_node_id` also opens at a consistent, readable position.
 * Renders nothing.
 */
const TreeInitialViewport = ({
  container_ref: containerRef,
  default_focus_center_x: defaultFocusCenterX,
  default_focus_top_y: defaultFocusTopY,
  default_zoom: defaultZoom,
}: TreeInitialViewportProps): ReactNode => {
  const { setViewport } = useReactFlow();

  useEffect(() => {
    const container = containerRef.current;

    if (!container) {
      return;
    }

    void setViewport(
      resolveTreeDefaultViewport({
        container_width: container.clientWidth,
        focus_center_x: defaultFocusCenterX,
        focus_top_y: defaultFocusTopY,
        default_zoom: defaultZoom,
      }),
      { duration: 0 }
    );
  }, [
    containerRef,
    defaultFocusCenterX,
    defaultFocusTopY,
    defaultZoom,
    setViewport,
  ]);

  return null;
};

export default TreeInitialViewport;
