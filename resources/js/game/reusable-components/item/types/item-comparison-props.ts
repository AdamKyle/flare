import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import { StateSetter } from '../../../../types/state-setter-type';
import { ItemComparison } from '../../../api-definitions/items/item-comparison-details';
import UsePurchaseAndReplaceApiRequestDefinition from '../../../components/shop/api/hooks/definitions/use-purchase-and-replace-api-request-definition';

export default interface ItemComparisonProps {
  comparisonDetails: ItemComparison;
  item_name: string;
  show_buy_and_replace?: boolean;
  is_purchasing: boolean;
  error_message?: AxiosErrorDefinition | null;
  set_request_params: StateSetter<UsePurchaseAndReplaceApiRequestDefinition>;
}
