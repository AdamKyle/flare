import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

const VALIDATABLE_GAME_MAP_FORM_FIELDS: readonly (keyof GameMapFormStateDefinition)[] =
  [
    'name',
    'description',
    'kingdom_color',
    'xp_bonus',
    'skill_training_bonus',
    'drop_chance_bonus',
    'enemy_stat_bonus',
    'character_attack_reduction',
    'required_location_id',
    'replacement_image_acknowledged',
  ];

export const isGameMapValidatableField = (
  field: keyof GameMapFormStateDefinition
): field is keyof GameMapFormErrorsDefinition &
  keyof GameMapFormStateDefinition =>
  VALIDATABLE_GAME_MAP_FORM_FIELDS.includes(field);
