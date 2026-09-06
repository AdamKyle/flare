import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MapGemFormDefinition from '../../api/definitions/map-gem-form-definition';
import MapGemFormOptionsDefinition from '../../api/definitions/map-gem-form-options-definition';
import MapGemFormErrorsDefinition from '../../definitions/map-gem-form-errors-definition';
import MapGemFormStateDefinition from '../../definitions/map-gem-form-state-definition';

export default interface UseMapGemFormDefinition {
  form_state: MapGemFormStateDefinition;
  update_field: <K extends keyof MapGemFormStateDefinition>(
    field: K,
    value: MapGemFormStateDefinition[K]
  ) => void;
  form_options: MapGemFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: MapGemFormErrorsDefinition;
  form_error: string | null;
  submit: () => Promise<MapGemFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
