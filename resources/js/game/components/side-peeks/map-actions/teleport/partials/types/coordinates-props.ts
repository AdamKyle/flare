import { Coordinates } from '../../api/hooks/definitions/teleport-coordinates-api-definition';

export default interface CoordinatesProps {
  coordinates: Coordinates;
  on_select_coordinates: (selectedCoordinates: {
    x: number;
    y: number;
  }) => void;
  x: number;
  y: number;
}
