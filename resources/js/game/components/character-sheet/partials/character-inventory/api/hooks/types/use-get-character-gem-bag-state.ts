import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import BaseGemDetails from '../../../../../../../api-definitions/items/base-gem-details';

export default interface UseGetCharacterGemBagState {
  data: BaseGemDetails[] | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
}
