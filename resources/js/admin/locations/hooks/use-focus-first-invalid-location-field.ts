import UseFocusFirstInvalidLocationFieldDefinition from './definitions/use-focus-first-invalid-location-field-definition';
import { useInvalidLocationFieldTarget } from './use-invalid-location-field-target';
import { useLocationFieldFocus } from './use-location-field-focus';
import LocationFormErrors from '../types/location-form-errors';

export const useFocusFirstInvalidLocationField = (
  fieldErrors: LocationFormErrors,
  setCurrentStepIndex: (index: number) => void
): UseFocusFirstInvalidLocationFieldDefinition => {
  const { pending_field_id, clear_pending_field_id, record_attempt } =
    useInvalidLocationFieldTarget(fieldErrors, setCurrentStepIndex);

  useLocationFieldFocus(pending_field_id, clear_pending_field_id);

  return {
    record_attempt,
  };
};
