import { MarketHistoryForTypeFilters } from '../../../components/market/api/enums/market-history-for-type-filters';

export const FILTER_LABELS: Record<MarketHistoryForTypeFilters, string> = {
  [MarketHistoryForTypeFilters.SINGLE_ENCHANT]: 'Single Enchant',
  [MarketHistoryForTypeFilters.DOUBLE_ENCHANT]: 'Double Enchant',
  [MarketHistoryForTypeFilters.UNIQUE]: 'Unique',
  [MarketHistoryForTypeFilters.MYTHIC]: 'Mythic',
  [MarketHistoryForTypeFilters.COSMIC]: 'Cosmic',
};

export const FILTER_OPTIONS: MarketHistoryForTypeFilters[] = [
  MarketHistoryForTypeFilters.SINGLE_ENCHANT,
  MarketHistoryForTypeFilters.DOUBLE_ENCHANT,
  MarketHistoryForTypeFilters.UNIQUE,
  MarketHistoryForTypeFilters.MYTHIC,
  MarketHistoryForTypeFilters.COSMIC,
];
