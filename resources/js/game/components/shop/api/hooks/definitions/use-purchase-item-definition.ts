import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UsePurchaseItemRequestDefinition from './use-purchase-item-request-definition';
import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface UsePurchaseItemDefinition {
  successMessage: string | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: StateSetter<UsePurchaseItemRequestDefinition>;
}
