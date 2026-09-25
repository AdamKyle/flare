import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseListItemOnMarketResponseDefinition from './use-list-item-on-market-response-definition';

export default interface UseListItemOnMarketDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  list_item: (
    slotId: number,
    listFor: number
  ) => Promise<UseListItemOnMarketResponseDefinition | null>;
}
