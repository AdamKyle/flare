import UpdatePositionParams from './update-position-params';
import { StateSetter } from '../../../../../types/state-setter-type';

export default interface UseProcessDirectionalMovementDefinition {
  setUpdateCharacterPosition: StateSetter<boolean>;
  updatePosition: (params: UpdatePositionParams) => void;
}
