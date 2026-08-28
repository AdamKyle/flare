import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcFormOptionsDefinition from '../../api/definitions/npc-form-options-definition';
import NpcFormErrors from '../../types/npc-form-errors';
import NpcFormState from '../../types/npc-form-state';

export default interface UseNpcFormDefinition {
  form_state: NpcFormState;
  update_field: <K extends keyof NpcFormState>(
    field: K,
    value: NpcFormState[K]
  ) => void;
  form_options: NpcFormOptionsDefinition | null;
  loading: boolean;
  load_error: AxiosErrorDefinition | null;
  saving: boolean;
  save_error: AxiosErrorDefinition | null;
  field_errors: NpcFormErrors;
  request_next: (step_index: number) => boolean;
  submit: () => Promise<boolean>;
  clear_errors: () => void;
}
