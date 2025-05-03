import { MapMovementTypes } from '../../../actions/partials/floating-cards/map-section/map-movement-types/map-movement-types';

export default interface UpdatePositionParams {
  baseX: number;
  baseY: number;
  movementAmount: number;
  movementType: MapMovementTypes | null;
}
