import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import LocationGemFormDefinition from '../../api/definitions/location-gem-form-definition';
import LocationGemFormOptionsDefinition from '../../api/definitions/location-gem-form-options-definition';
import LocationGemFormErrorsDefinition from '../../definitions/location-gem-form-errors-definition';
import LocationGemFormStateDefinition from '../../definitions/location-gem-form-state-definition';

export default interface UseLocationGemFormDefinition {
  form_state: LocationGemFormStateDefinition;
  update_field: <K extends keyof LocationGemFormStateDefinition>(
    field: K,
    value: LocationGemFormStateDefinition[K]
  ) => void;
  form_options: LocationGemFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: LocationGemFormErrorsDefinition;
  form_error: string | null;
  submit: () => Promise<LocationGemFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
