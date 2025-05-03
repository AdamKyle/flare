import { MapMovementTypes } from '../../map-movement-types/map-movement-types';

export default interface UseDirectionallyMoveCharacter {
  movementAmount: number;
  movementType: MapMovementTypes | null;
  moveCharacterDirectionally: (
    amount: number,
    direction: MapMovementTypes
  ) => void;
}
