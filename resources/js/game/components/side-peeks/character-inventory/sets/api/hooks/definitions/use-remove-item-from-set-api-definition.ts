import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import RemoveItemFromSetRequestDefinition from '../../definitions/remove-item-from-set-request-definition';

export default interface UseRemoveItemFromSetApiDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  removeItemFromSet: (
    request: RemoveItemFromSetRequestDefinition
  ) => Promise<void>;
}
