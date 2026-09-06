import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import RaceDefinition from '../../api/definitions/race-definition';
import RaceFormErrorsDefinition from '../../definitions/race-form-errors-definition';
import RaceFormStateDefinition from '../../definitions/race-form-state-definition';

export default interface UseRaceFormDefinition {
  form_state: RaceFormStateDefinition;
  update_field: <K extends keyof RaceFormStateDefinition>(
    field: K,
    value: RaceFormStateDefinition[K]
  ) => void;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: RaceFormErrorsDefinition;
  form_error: string | null;
  submit: () => Promise<RaceDefinition | null>;
  validate_step: () => boolean;
}
