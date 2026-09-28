import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GemAbilityFormDefinition from '../../api/definitions/gem-ability-form-definition';
import GemAbilityFormOptionsDefinition from '../../api/definitions/gem-ability-form-options-definition';
import GemAbilityFormErrorsDefinition from '../../definitions/gem-ability-form-errors-definition';
import GemAbilityFormStateDefinition from '../../definitions/gem-ability-form-state-definition';

export default interface UseGemAbilityFormDefinition {
  form_state: GemAbilityFormStateDefinition;
  update_field: <K extends keyof GemAbilityFormStateDefinition>(
    field: K,
    value: GemAbilityFormStateDefinition[K]
  ) => void;
  form_options: GemAbilityFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: GemAbilityFormErrorsDefinition;
  form_error: string | null;
  submit: () => Promise<GemAbilityFormDefinition | null>;
  validate_step: (step_index: number) => boolean;
}
