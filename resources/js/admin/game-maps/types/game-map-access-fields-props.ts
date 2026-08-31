import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormOptionsDefinition from '../definitions/game-map-form-options-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

export default interface GameMapAccessFieldsProps {
  state: GameMapFormStateDefinition;
  errors: GameMapFormErrorsDefinition;
  form_options: GameMapFormOptionsDefinition;
  on_change: <K extends keyof GameMapFormStateDefinition>(
    field: K,
    value: GameMapFormStateDefinition[K]
  ) => void;
}
