import { RefObject } from 'react';

export default interface TreeInitialViewportProps {
  container_ref: RefObject<HTMLDivElement | null>;
  default_focus_center_x: number;
  default_focus_top_y: number;
  default_zoom: number;
}
