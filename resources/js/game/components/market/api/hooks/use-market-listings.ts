import PaginatedApiHandlerDefinition from 'api-handler/definitions/paginated-api-handler-definition';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';

import MarketListingDefinition from '../definitions/market-listing-definition';
import MarketListingFiltersDefinition from '../definitions/market-listing-filters-definition';
import { MarketApis } from '../enums/market-apis';

export const useMarketListings = (): PaginatedApiHandlerDefinition<
  MarketListingDefinition,
  MarketListingFiltersDefinition
> =>
  UsePaginatedApiHandler<
    MarketListingDefinition,
    MarketListingFiltersDefinition
  >({ url: MarketApis.LISTINGS });
