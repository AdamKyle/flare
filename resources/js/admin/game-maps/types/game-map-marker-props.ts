import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';

export default interface GameMapMarkerProps {
  variant: GameMapMarkerVariant;
  left: number;
  top: number;
  accessible_name: string;
  on_activate: () => void;
}
