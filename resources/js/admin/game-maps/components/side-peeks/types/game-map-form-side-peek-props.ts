import GameMapFormResponseDefinition from '../../../definitions/game-map-form-response-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GameMapFormSidePeekProps extends SidePeekProps {
  game_map_id: number;
  on_saved: (game_map: GameMapFormResponseDefinition) => void;
}
