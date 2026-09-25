import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MarketBuyAndReplaceRequestDefinition from '../../definitions/market-buy-and-replace-request-definition';
import MarketPurchaseResponseDefinition from '../../definitions/market-purchase-response-definition';

export default interface UseBuyAndReplaceMarketListingDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  buy_and_replace: (
    listingId: number,
    request: MarketBuyAndReplaceRequestDefinition
  ) => Promise<MarketPurchaseResponseDefinition | null>;
}
