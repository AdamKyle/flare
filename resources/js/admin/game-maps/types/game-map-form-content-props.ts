import GameMapFormResponseDefinition from '../definitions/game-map-form-response-definition';

export default interface GameMapFormContentProps {
  game_map_id: number | null;
  on_saved: (game_map: GameMapFormResponseDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
