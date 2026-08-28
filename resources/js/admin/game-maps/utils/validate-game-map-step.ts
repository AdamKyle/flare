import { validateGameMapAccessStep } from './validate-game-map-access-step';
import { validateGameMapBonusesStep } from './validate-game-map-bonuses-step';
import { validateGameMapMapStep } from './validate-game-map-map-step';
import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormOptionsDefinition from '../definitions/game-map-form-options-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

/**
 * Validate the single Game Map wizard step identified by `step_index`.
 */
export const validateGameMapStep = (
  step_index: number,
  state: GameMapFormStateDefinition,
  is_create: boolean,
  options: GameMapFormOptionsDefinition | null
): GameMapFormErrorsDefinition => {
  switch (step_index) {
    case 0:
      return validateGameMapMapStep(state, is_create);
    case 1:
      return validateGameMapBonusesStep(state);
    case 2:
      return validateGameMapAccessStep(state, options);
    default:
      // The Game Map wizard only ever registers three steps (0-2); an index outside
      // that range has no corresponding step to validate.
      return {};
  }
};
