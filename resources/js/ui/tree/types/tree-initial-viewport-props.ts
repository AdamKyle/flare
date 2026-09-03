import { RefObject } from 'react';

export default interface TreeInitialViewportProps {
  container_ref: RefObject<HTMLDivElement | null>;
  root_center_x: number;
  root_top_y: number;
}
