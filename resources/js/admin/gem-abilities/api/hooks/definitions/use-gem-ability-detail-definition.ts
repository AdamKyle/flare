import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import GemAbilityDetailDefinition from '../../definitions/gem-ability-detail-definition';

export default interface UseGemAbilityDetailDefinition {
  gem_ability: GemAbilityDetailDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
