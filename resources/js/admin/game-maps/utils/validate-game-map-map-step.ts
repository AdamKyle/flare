import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';
import { GameMapFormValidationMessages } from '../enums/game-map-form-validation-messages';

/**
 * Validate the Game Map wizard's Map step: name, Kingdom color, and a required map
 * image when creating a new Game Map.
 */
export const validateGameMapMapStep = (
  state: GameMapFormStateDefinition,
  is_create: boolean
): GameMapFormErrorsDefinition => {
  const stepErrors: GameMapFormErrorsDefinition = {};

  if (state.name.trim() === '') {
    stepErrors.name = GameMapFormValidationMessages.NameRequired;
  }

  if (state.kingdom_color.trim() === '') {
    stepErrors.kingdom_color =
      GameMapFormValidationMessages.KingdomColorRequired;
  }

  if (is_create && state.map === null) {
    stepErrors.map = GameMapFormValidationMessages.MapRequired;
  }

  if (
    !is_create &&
    state.map !== null &&
    !state.replacement_image_acknowledged
  ) {
    stepErrors.replacement_image_acknowledged =
      GameMapFormValidationMessages.ReplacementAcknowledgementRequired;
  }

  return stepErrors;
};
