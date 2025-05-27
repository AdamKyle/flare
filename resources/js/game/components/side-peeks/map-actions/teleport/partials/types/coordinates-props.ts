import { Coordinates } from '../../api/hooks/definitions/teleport-coordinates-api-definition';
import { CoordinateTypes } from '../../enums/coordinate-types';

export default interface CoordinatesProps {
  coordinates: Coordinates;
  on_select_coordinates: (selectedCoordinates: {
    x: number;
    y: number;
  }) => void;
  on_clear_coordinates: (coordinatesType: CoordinateTypes) => void;
  x: number;
  y: number;
}
