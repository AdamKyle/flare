import { StateSetter } from '../../../../../types/state-setter-type';

export default interface UseProcessDirectionalMovementParams {
  onPositionChange: StateSetter<{
    character_position_x: number;
    character_position_y: number;
  }>;
}
