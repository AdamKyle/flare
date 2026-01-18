import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import UseGetMarketHistoryForTypeRequestParams from './use-get-market-history-for-type-request-params';
import MarketHistoryForTypeResponseDefinition from './use-get-market-history-for-type-response-definition';
import { StateSetter } from '../../../../../types/state-setter-type';

export default interface UseGetMarketHistoryForTypeDefinition {
  error: AxiosErrorDefinition | null;
  loading: boolean;
  data: MarketHistoryForTypeResponseDefinition[] | [];
  setRequestParams: StateSetter<UseGetMarketHistoryForTypeRequestParams>;
}
