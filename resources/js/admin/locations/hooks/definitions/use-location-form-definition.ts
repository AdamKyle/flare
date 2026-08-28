import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationFormOptionsDefinition from '../../api/definitions/location-form-options-definition';
import LocationFormErrors from '../../types/location-form-errors';
import LocationFormState from '../../types/location-form-state';

export default interface UseLocationFormDefinition {
  form_state: LocationFormState;
  update_field: <K extends keyof LocationFormState>(
    field: K,
    value: LocationFormState[K]
  ) => void;
  form_options: LocationFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: LocationFormErrors;
  request_next: (step_index: number) => boolean;
  submit: () => Promise<boolean>;
  clear_errors: () => void;
}
