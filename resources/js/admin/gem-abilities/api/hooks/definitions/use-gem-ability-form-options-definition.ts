import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GemAbilityFormOptionsDefinition from '../../definitions/gem-ability-form-options-definition';

export default interface UseGemAbilityFormOptionsDefinition {
  form_options: GemAbilityFormOptionsDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
