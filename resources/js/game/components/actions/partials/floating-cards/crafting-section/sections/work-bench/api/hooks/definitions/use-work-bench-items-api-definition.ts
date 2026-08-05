import WorkBenchInventorySlotDefinition from '../../definitions/work-bench-inventory-slot-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseWorkBenchItemsApiDefinition {
  items: DropdownItem[];
  loadedItems: WorkBenchInventorySlotDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
