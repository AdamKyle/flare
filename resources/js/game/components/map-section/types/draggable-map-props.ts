import MapIcon from './map-icon';

export default interface DraggableMapProps {
  tiles: string[][];
  map_icons?: MapIcon[];
  character: MapIcon;
  additional_css: string;
  on_click: (mapIcon: MapIcon) => void;
  zoom?: number;
}
