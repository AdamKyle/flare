import GameMapKingdomMarkerDefinition from '../../../api/definitions/game-map-kingdom-marker-definition';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface GameMapKingdomSidePeekProps extends SidePeekProps {
  kingdom: GameMapKingdomMarkerDefinition;
}
