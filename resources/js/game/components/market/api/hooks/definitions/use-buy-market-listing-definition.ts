import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MarketPurchaseResponseDefinition from '../../definitions/market-purchase-response-definition';

export default interface UseBuyMarketListingDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  buy: (listingId: number) => Promise<MarketPurchaseResponseDefinition | null>;
}
