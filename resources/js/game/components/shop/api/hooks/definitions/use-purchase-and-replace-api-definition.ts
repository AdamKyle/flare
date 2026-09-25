import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UsePurchaseAndReplaceApiRequestDefinition from './use-purchase-and-replace-api-request-definition';
import UsePurchaseAndReplaceApiResponseDefinition from './use-purchase-and-replace-api-response-definition';

export default interface UsePurchaseAndReplaceApiDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  mutate: (
    request: UsePurchaseAndReplaceApiRequestDefinition
  ) => Promise<UsePurchaseAndReplaceApiResponseDefinition | null>;
}
