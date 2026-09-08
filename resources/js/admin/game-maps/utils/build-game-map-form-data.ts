import { convertPercentageToStoredBonus } from './convert-percentage-to-stored-bonus';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';

/**
 * Updates use POST with `_method=PUT` so the image remains a multipart upload.
 */
export const buildGameMapFormData = (
  state: GameMapFormStateDefinition,
  gameMapId: number | null
): FormData => {
  const formData = new FormData();

  if (gameMapId !== null) {
    formData.append('_method', 'PUT');
  }

  formData.append('name', state.name.trim());
  formData.append('description', state.description);
  formData.append('kingdom_color', state.kingdom_color);
  formData.append('default', state.default ? '1' : '0');
  formData.append('can_traverse', state.can_traverse ? '1' : '0');
  formData.append(
    'xp_bonus',
    String(convertPercentageToStoredBonus(Number(state.xp_bonus)))
  );
  formData.append(
    'skill_training_bonus',
    String(convertPercentageToStoredBonus(Number(state.skill_training_bonus)))
  );
  formData.append(
    'drop_chance_bonus',
    String(convertPercentageToStoredBonus(Number(state.drop_chance_bonus)))
  );
  formData.append(
    'enemy_stat_bonus',
    String(convertPercentageToStoredBonus(Number(state.enemy_stat_bonus)))
  );
  formData.append(
    'character_attack_reduction',
    String(
      convertPercentageToStoredBonus(Number(state.character_attack_reduction))
    )
  );

  if (state.only_during_event_type !== null) {
    formData.append(
      'only_during_event_type',
      String(state.only_during_event_type)
    );
  }

  if (state.required_location_id !== null) {
    formData.append('required_location_id', String(state.required_location_id));
  }

  if (state.map !== null) {
    formData.append('map', state.map);
  }

  return formData;
};
