import { ReactNode } from 'react';

import MapIcon from './map-icon';

export default interface DraggableMapProps {
  character: MapIcon;
  tiles: string[][];
  additional_css: string;
  zoom?: number;
  children: ReactNode[];
}
