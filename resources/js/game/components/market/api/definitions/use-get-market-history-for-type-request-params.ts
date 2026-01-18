import { InventoryItemTypes } from '../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { MarketHistoryForTypeFilters } from '../enums/market-history-for-type-filters';

export default interface UseGetMarketHistoryForTypeRequestParams {
  type: InventoryItemTypes | null;
  filter: MarketHistoryForTypeFilters | null;
}
