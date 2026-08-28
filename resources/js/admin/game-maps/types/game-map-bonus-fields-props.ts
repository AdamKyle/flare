import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

export default interface GameMapBonusFieldsProps {
  state: GameMapFormStateDefinition;
  errors: GameMapFormErrorsDefinition;
  on_change: <K extends keyof GameMapFormStateDefinition>(
    field: K,
    value: GameMapFormStateDefinition[K]
  ) => void;
}
