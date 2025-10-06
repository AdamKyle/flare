import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UsePurchaseAndReplaceApiRequestDefinition from './use-purchase-and-replace-api-request-definition';
import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface UsePurchaseAndReplaceApiDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: StateSetter<UsePurchaseAndReplaceApiRequestDefinition>;
}
