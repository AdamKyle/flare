import { useReactFlow } from '@xyflow/react';
import { ReactNode, useEffect } from 'react';

import TreeInitialViewportProps from '../types/tree-initial-viewport-props';

const TOP_MARGIN_PX = 56;

/**
 * Positions the Tree viewport at a fixed, readable zoom of 1 with the
 * root/root-group visible near the upper-central portion of the container,
 * instead of relying on React Flow's automatic whole-tree `fitView` (which
 * crushes a large Tree, such as a tall Raid hierarchy, to a tiny overview on
 * first render). Re-centers whenever the resolved root anchor changes, so a
 * newly selected Plane/Raid tab also opens at a consistent, readable
 * position. Renders nothing.
 */
const TreeInitialViewport = ({
  container_ref: containerRef,
  root_center_x: rootCenterX,
  root_top_y: rootTopY,
}: TreeInitialViewportProps): ReactNode => {
  const { setViewport } = useReactFlow();

  useEffect(() => {
    const container = containerRef.current;

    if (!container) {
      return;
    }

    void setViewport(
      {
        x: container.clientWidth / 2 - rootCenterX,
        y: TOP_MARGIN_PX - rootTopY,
        zoom: 1,
      },
      { duration: 0 }
    );
  }, [containerRef, rootCenterX, rootTopY, setViewport]);

  return null;
};

export default TreeInitialViewport;
