import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MarketHistoryForTypeResponseDefinition from './use-get-market-history-for-type-response-definition';

export default interface UseGetMarketHistoryForTypeDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  data: MarketHistoryForTypeResponseDefinition[];
}
