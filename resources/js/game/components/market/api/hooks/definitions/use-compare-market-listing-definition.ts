import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MarketComparisonResponseDefinition from '../../definitions/market-comparison-response-definition';

export default interface UseCompareMarketListingDefinition {
  data: MarketComparisonResponseDefinition | null;
  loading: boolean;
  error: AxiosErrorDefinition | null;
}
