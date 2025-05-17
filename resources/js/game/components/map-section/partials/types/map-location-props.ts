import MapIcon from '../../types/map-icon';

export default interface MapLocationProps {
  mapIcons: MapIcon[];
  onClick: (icon: MapIcon) => void;
  zoom: number;
}
