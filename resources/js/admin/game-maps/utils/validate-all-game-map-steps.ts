import { validateGameMapAccessStep } from './validate-game-map-access-step';
import { validateGameMapBonusesStep } from './validate-game-map-bonuses-step';
import { validateGameMapMapStep } from './validate-game-map-map-step';
import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormOptionsDefinition from '../definitions/game-map-form-options-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

export const validateAllGameMapSteps = (
  state: GameMapFormStateDefinition,
  is_create: boolean,
  options: GameMapFormOptionsDefinition | null
): GameMapFormErrorsDefinition => ({
  ...validateGameMapMapStep(state, is_create),
  ...validateGameMapBonusesStep(state),
  ...validateGameMapAccessStep(state, options),
});
