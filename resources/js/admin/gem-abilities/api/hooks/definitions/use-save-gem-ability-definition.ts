import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GemAbilityFormDefinition from '../../definitions/gem-ability-form-definition';
import GemAbilityRequestDefinition from '../../definitions/gem-ability-request-definition';

export default interface UseSaveGemAbilityDefinition {
  saving: boolean;
  error: AxiosErrorDefinition | null;
  field_errors: Record<string, string>;
  save: (
    gem_ability_id: number | null,
    payload: GemAbilityRequestDefinition
  ) => Promise<GemAbilityFormDefinition | null>;
  clear_field_error: (field: string) => void;
}
