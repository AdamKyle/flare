import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';

import MarketDelistResponseDefinition from '../../definitions/market-delist-response-definition';
import MarketListingResponseDefinition from '../../definitions/market-listing-response-definition';
import MarketMessageResponseDefinition from '../../definitions/market-message-response-definition';

export default interface UseManageMarketListingDefinition {
  loading: boolean;
  error: AxiosErrorDefinition | null;
  begin_edit: (
    listingId: number
  ) => Promise<MarketListingResponseDefinition | null>;
  update_price: (
    listingId: number,
    listedPrice: number
  ) => Promise<MarketListingResponseDefinition | null>;
  cancel_edit: (
    listingId: number
  ) => Promise<MarketMessageResponseDefinition | null>;
  delist: (listingId: number) => Promise<MarketDelistResponseDefinition | null>;
}
