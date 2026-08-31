import CoordinateDefinition from './coordinate-definition';
import SelectedCoordinateDefinition from './selected-coordinate-definition';
import GameMapEditorDefinition from '../api/definitions/game-map-editor-definition';
import GameMapKingdomMarkerDefinition from '../api/definitions/game-map-kingdom-marker-definition';
import { GameMapMarkerVariant } from '../enums/game-map-marker-variant';

export interface MovingRecordDefinition {
  kind: GameMapMarkerVariant.Location | GameMapMarkerVariant.Npc;
  id: number;
  label: string;
  origin_x: number;
  origin_y: number;
}

export default interface GameMapEditorCanvasProps {
  editor: GameMapEditorDefinition;
  selected_coordinate: SelectedCoordinateDefinition | null;
  on_select_coordinate: (
    coordinate: SelectedCoordinateDefinition | null
  ) => void;
  moving_record: MovingRecordDefinition | null;
  on_move_target_selected: (coordinate: CoordinateDefinition) => void;
  on_cancel_active_mode: () => void;
  reset_token: number;
  focus_token: number;
  on_location_selected: (location_id: number) => void;
  on_npc_selected: (npc_id: number) => void;
  on_kingdom_selected: (kingdom: GameMapKingdomMarkerDefinition) => void;
}
