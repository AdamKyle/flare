import { MarketHistoryForTypeFilters } from '../enums/market-history-for-type-filters';

export default interface UseGetMarketHistoryForTypeRequestParams {
  type: string;
  filter: MarketHistoryForTypeFilters | null;
}
