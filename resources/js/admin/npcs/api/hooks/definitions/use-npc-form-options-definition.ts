import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import NpcFormOptionsDefinition from '../../definitions/npc-form-options-definition';

export default interface UseNpcFormOptionsDefinition {
  form_options: NpcFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
