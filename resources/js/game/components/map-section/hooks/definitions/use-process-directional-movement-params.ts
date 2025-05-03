import { StateSetter } from '../../../../../types/state-setter-type';
import CharacterMapPosition from '../../types/character-map-position';

export default interface UseProcessDirectionalMovementParams {
  onPositionChange: StateSetter<{
    character_position_x: number;
    character_position_y: number;
  }>;
  onCharacterPositionChange: StateSetter<CharacterMapPosition>;
}
