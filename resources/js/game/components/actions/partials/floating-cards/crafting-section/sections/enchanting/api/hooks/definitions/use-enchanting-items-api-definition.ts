import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseEnchantingItemsApiDefinition {
  items: DropdownItem[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
  refreshItems: () => void;
}
