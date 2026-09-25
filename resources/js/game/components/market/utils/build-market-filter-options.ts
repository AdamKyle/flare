import { MarketListingPriceSort } from '../api/enums/market-listing-price-sort';
import { MARKET_ITEM_TYPE_LABELS } from '../constants/market-item-type-labels';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const MARKET_TYPE_OPTIONS: DropdownItem[] = Object.entries(
  MARKET_ITEM_TYPE_LABELS
).map(([value, label]) => ({ label, value }));

export const MARKET_PRICE_SORT_OPTIONS: DropdownItem[] = [
  { label: 'Price: Low to High', value: MarketListingPriceSort.ASCENDING },
  { label: 'Price: High to Low', value: MarketListingPriceSort.DESCENDING },
];

export const resolveMarketPriceSort = (
  option: DropdownItem
): MarketListingPriceSort | null => {
  if (option.value === MarketListingPriceSort.ASCENDING) {
    return MarketListingPriceSort.ASCENDING;
  }

  if (option.value === MarketListingPriceSort.DESCENDING) {
    return MarketListingPriceSort.DESCENDING;
  }

  return null;
};
