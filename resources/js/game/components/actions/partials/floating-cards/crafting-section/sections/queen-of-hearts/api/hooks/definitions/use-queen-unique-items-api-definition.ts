import QueenInventorySlotDefinition from '../../definitions/queen-inventory-slot-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseQueenUniqueItemsApiDefinition {
  items: DropdownItem[];
  loadedItems: QueenInventorySlotDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
  refresh: () => void;
}
