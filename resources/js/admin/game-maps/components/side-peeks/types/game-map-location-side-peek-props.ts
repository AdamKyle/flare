import GameMapLocationMarkerDefinition from '../../../api/definitions/game-map-location-marker-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GameMapLocationSidePeekProps extends SidePeekProps {
  game_map_id: number;
  location_id: number;
  location_marker: GameMapLocationMarkerDefinition;
  on_editor_changed: () => Promise<void> | void;
  on_move_requested: (location_id: number) => void;
}
