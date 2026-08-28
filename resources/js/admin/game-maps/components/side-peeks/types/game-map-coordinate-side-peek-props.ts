import GameMapLocationMarkerDefinition from '../../../api/definitions/game-map-location-marker-definition';
import GameMapNpcMarkerDefinition from '../../../api/definitions/game-map-npc-marker-definition';
import { GameMapMoveStateDefinition } from '../../../events/definitions/game-map-move-event-map';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GameMapCoordinateSidePeekProps extends SidePeekProps {
  game_map_id: number;
  x: number;
  y: number;
  locations: GameMapLocationMarkerDefinition[];
  npcs: GameMapNpcMarkerDefinition[];
  on_editor_changed: () => Promise<void> | void;
  initial_move_state: GameMapMoveStateDefinition;
}
