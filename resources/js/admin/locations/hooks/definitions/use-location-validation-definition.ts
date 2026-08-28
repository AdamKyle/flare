import LocationFormErrors from '../../types/location-form-errors';
import LocationFormState from '../../types/location-form-state';

export default interface UseLocationValidationDefinition {
  errors: LocationFormErrors;
  validate_step: (step_index: number, state: LocationFormState) => boolean;
  validate_all: (state: LocationFormState) => boolean;
  validate_field: (
    field: keyof LocationFormErrors,
    state: LocationFormState
  ) => void;
  clear_errors: () => void;
}
