import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GemAbilityFormDefinition from '../../definitions/gem-ability-form-definition';

export default interface UseGemAbilityForEditDefinition {
  gem_ability: GemAbilityFormDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
