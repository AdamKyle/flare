import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import MarketListingDefinition from '../definitions/market-listing-definition';
import { MarketApis } from '../enums/market-apis';

export const useOwnedMarketListings = (
  characterId: number
): PaginatedApiHandlerDefinition<
  MarketListingDefinition,
  Record<string, unknown>
> =>
  UsePaginatedApiHandler<MarketListingDefinition>({
    url: MarketApis.CURRENT_LISTINGS,
    urlParams: { character: characterId },
    enabled: characterId > 0,
  });
