import { RefObject } from 'react';

export default interface TreeControlsProps {
  container_ref: RefObject<HTMLDivElement | null>;
  on_fullscreen_change?: (is_fullscreen: boolean) => void;
}
