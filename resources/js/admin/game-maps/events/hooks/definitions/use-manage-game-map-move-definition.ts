import CoordinateDefinition from '../../../types/coordinate-definition';
import { MovingRecordDefinition } from '../../../types/game-map-editor-canvas-props';
import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

export default interface UseManageGameMapMoveDefinition {
  moving_record: MovingRecordDefinition | null;
  pending_move_target: CoordinateDefinition | null;
  is_moving: boolean;
  move_error: AxiosErrorDefinition | null;
  start_move_location: (
    location_id: number,
    label: string,
    origin_x: number,
    origin_y: number
  ) => void;
  start_move_npc: (
    npc_id: number,
    label: string,
    origin_x: number,
    origin_y: number
  ) => void;
  select_move_target: (coordinate: CoordinateDefinition) => void;
  confirm_move: () => void;
  cancel_move: () => void;
  clear_move_error: () => void;
}
