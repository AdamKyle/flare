import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import CoordinateDefinition from '../../types/coordinate-definition';
import { MovingRecordDefinition } from '../../types/game-map-editor-canvas-props';
import { GameMapMoveEvent } from '../enums/game-map-move-event';

export interface GameMapMoveStateDefinition {
  moving_record: MovingRecordDefinition | null;
  pending_move_target: CoordinateDefinition | null;
  is_moving: boolean;
  move_error: AxiosErrorDefinition | null;
}

export interface GameMapMoveStartCommandDefinition {
  record_id: number;
  label: string;
  origin_x: number;
  origin_y: number;
}

export interface GameMapMoveSucceededDefinition {
  moving_record: MovingRecordDefinition;
}

export type GameMapMoveEventMap = {
  [GameMapMoveEvent.STATE_CHANGED]: GameMapMoveStateDefinition;
  [GameMapMoveEvent.START_LOCATION]: GameMapMoveStartCommandDefinition;
  [GameMapMoveEvent.START_NPC]: GameMapMoveStartCommandDefinition;
  [GameMapMoveEvent.SELECT_TARGET]: CoordinateDefinition;
  [GameMapMoveEvent.CONFIRM]: undefined;
  [GameMapMoveEvent.CANCEL]: undefined;
  [GameMapMoveEvent.CLEAR_ERROR]: undefined;
  [GameMapMoveEvent.SUCCEEDED]: GameMapMoveSucceededDefinition;
};
