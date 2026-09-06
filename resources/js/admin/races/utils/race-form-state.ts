import RaceDefinition from '../api/definitions/race-definition';
import RaceFormErrorsDefinition from '../definitions/race-form-errors-definition';
import RaceFormStateDefinition from '../definitions/race-form-state-definition';
import RaceValidationResultDefinition from '../definitions/race-validation-result-definition';

export const createRaceFormState = (
  race: RaceDefinition | null
): RaceFormStateDefinition => {
  if (!race) {
    return {
      name: '',
      description: '',
      image: null,
      current_image_url: null,
    };
  }

  return {
    name: race.name,
    description: race.description ?? '',
    image: null,
    current_image_url: race.image_url,
  };
};

/**
 * Build the multipart Race save request payload from validated form state.
 * Update requests are sent as `POST` with a spoofed `_method=PUT` field so the
 * Race image can be uploaded in the same multipart request.
 */
export const buildRaceFormData = (
  state: RaceFormStateDefinition,
  raceId: number | null
): FormData => {
  const formData = new FormData();

  if (raceId !== null) {
    formData.append('_method', 'PUT');
  }

  formData.append('name', state.name.trim());
  formData.append('description', state.description);

  if (state.image !== null) {
    formData.append('image', state.image);
  }

  return formData;
};

export const validateRaceForm = (
  state: RaceFormStateDefinition
): RaceValidationResultDefinition => {
  const errors: RaceFormErrorsDefinition = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Race name.';
  }

  return {
    is_valid: Object.keys(errors).length === 0,
    field_errors: errors,
    form_error: null,
  };
};
