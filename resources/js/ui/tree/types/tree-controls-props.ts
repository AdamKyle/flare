import { RefObject } from 'react';

export default interface TreeControlsProps {
  container_ref: RefObject<HTMLDivElement | null>;
  default_focus_center_x: number;
  default_focus_top_y: number;
  default_zoom: number;
  on_fullscreen_change?: (is_fullscreen: boolean) => void;
}
