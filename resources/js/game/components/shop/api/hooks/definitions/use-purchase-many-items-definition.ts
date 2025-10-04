import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UsePurchaseManyItemsRequestDefinition from './use-purchase-many-items-request-definition';
import { StateSetter } from '../../../../../../types/state-setter-type';

export default interface UsePurchaseManyItemsDefinition {
  successMessage: string | null;
  error: AxiosErrorDefinition | null;
  loading: boolean;
  setRequestParams: StateSetter<UsePurchaseManyItemsRequestDefinition>;
}
