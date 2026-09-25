import { MarketListingPriceSort } from '../enums/market-listing-price-sort';

export default interface MarketListingFiltersDefinition extends Record<
  string,
  unknown
> {
  type: string | null;
  sort_price: MarketListingPriceSort | null;
}
