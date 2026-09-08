import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormOptionsDefinition from '../definitions/game-map-form-options-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';
import { GameMapFormValidationMessages } from '../enums/game-map-form-validation-messages';

export const validateGameMapAccessStep = (
  state: GameMapFormStateDefinition,
  options: GameMapFormOptionsDefinition | null
): GameMapFormErrorsDefinition => {
  const stepErrors: GameMapFormErrorsDefinition = {};

  if (
    state.required_location_id !== null &&
    !options?.locations.some(
      (location) => location.id === state.required_location_id
    )
  ) {
    stepErrors.required_location_id =
      GameMapFormValidationMessages.InvalidRequiredLocation;
  }

  return stepErrors;
};
