import CoordinateDefinition from '../../../types/coordinate-definition';
import {
  GameMapMoveStartCommandDefinition,
  GameMapMoveStateDefinition,
  GameMapMoveSucceededDefinition,
} from '../../definitions/game-map-move-event-map';

export default interface UseGameMapMoveEmitterDefinition {
  emit_state: (state: GameMapMoveStateDefinition) => void;
  start_location: (command: GameMapMoveStartCommandDefinition) => void;
  start_npc: (command: GameMapMoveStartCommandDefinition) => void;
  select_target: (coordinate: CoordinateDefinition) => void;
  confirm: () => void;
  cancel: () => void;
  clear_error: () => void;
  emit_succeeded: (result: GameMapMoveSucceededDefinition) => void;
}
