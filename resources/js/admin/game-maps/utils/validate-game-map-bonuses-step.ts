import { isValidNumericString } from './is-valid-numeric-string';
import { GameMapFormValidationMessages } from '../enums/game-map-form-validation-messages';
import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

/**
 * Validate the Game Map wizard's Bonuses step: each percentage field must be a
 * valid numeric string.
 */
export const validateGameMapBonusesStep = (
  state: GameMapFormStateDefinition
): GameMapFormErrorsDefinition => {
  const stepErrors: GameMapFormErrorsDefinition = {};

  if (!isValidNumericString(state.xp_bonus)) {
    stepErrors.xp_bonus = GameMapFormValidationMessages.InvalidXpBonus;
  }

  if (!isValidNumericString(state.skill_training_bonus)) {
    stepErrors.skill_training_bonus =
      GameMapFormValidationMessages.InvalidSkillTrainingBonus;
  }

  if (!isValidNumericString(state.drop_chance_bonus)) {
    stepErrors.drop_chance_bonus =
      GameMapFormValidationMessages.InvalidDropChanceBonus;
  }

  if (!isValidNumericString(state.enemy_stat_bonus)) {
    stepErrors.enemy_stat_bonus =
      GameMapFormValidationMessages.InvalidEnemyStatBonus;
  }

  if (!isValidNumericString(state.character_attack_reduction)) {
    stepErrors.character_attack_reduction =
      GameMapFormValidationMessages.InvalidCharacterAttackReduction;
  }

  return stepErrors;
};
