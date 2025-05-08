import MapIcon from './map-icon';

export default interface DraggableMapProps {
  tiles: string[][];
  character_kingdom_icons: MapIcon[];
  location_icons: MapIcon[];
  character: MapIcon;
  additional_css: string;
  on_character_kingdom_click: (mapIcon: MapIcon) => void;
  on_location_click: (mapIcon: MapIcon) => void;
  zoom?: number;
}
