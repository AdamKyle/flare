import { GameMapMoveStateDefinition } from '../definitions/game-map-move-event-map';

export const buildDefaultGameMapMoveState = (): GameMapMoveStateDefinition => ({
  moving_record: null,
  pending_move_target: null,
  is_moving: false,
  move_error: null,
});
