import { convertStoredBonusToPercentage } from './convert-stored-bonus-to-percentage';
import GameMapFormResponseDefinition from '../definitions/game-map-form-response-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

const numberToPercentageString = (value: number): string =>
  String(convertStoredBonusToPercentage(value));

export const createGameMapFormState = (
  gameMap: GameMapFormResponseDefinition | null
): GameMapFormStateDefinition => {
  if (gameMap) {
    return {
      name: gameMap.name,
      description: gameMap.description ?? '',
      kingdom_color: gameMap.kingdom_color,
      default: gameMap.default,
      map: null,
      replacement_image_acknowledged: false,
      xp_bonus: numberToPercentageString(gameMap.xp_bonus),
      skill_training_bonus: numberToPercentageString(
        gameMap.skill_training_bonus
      ),
      drop_chance_bonus: numberToPercentageString(gameMap.drop_chance_bonus),
      enemy_stat_bonus: numberToPercentageString(gameMap.enemy_stat_bonus),
      character_attack_reduction: numberToPercentageString(
        gameMap.character_attack_reduction
      ),
      required_location_id: gameMap.required_location_id,
      can_traverse: gameMap.can_traverse,
      only_during_event_type: gameMap.only_during_event_type,
    };
  }

  return {
    name: '',
    description: '',
    kingdom_color: '#ffffff',
    default: false,
    map: null,
    replacement_image_acknowledged: false,
    xp_bonus: '0',
    skill_training_bonus: '0',
    drop_chance_bonus: '0',
    enemy_stat_bonus: '0',
    character_attack_reduction: '0',
    required_location_id: null,
    can_traverse: true,
    only_during_event_type: null,
  };
};
