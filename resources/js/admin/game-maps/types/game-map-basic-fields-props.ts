import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

export default interface GameMapBasicFieldsProps {
  state: GameMapFormStateDefinition;
  errors: GameMapFormErrorsDefinition;
  current_map_url: string | null;
  game_map_id: number | null;
  on_change: <K extends keyof GameMapFormStateDefinition>(
    field: K,
    value: GameMapFormStateDefinition[K]
  ) => void;
}
