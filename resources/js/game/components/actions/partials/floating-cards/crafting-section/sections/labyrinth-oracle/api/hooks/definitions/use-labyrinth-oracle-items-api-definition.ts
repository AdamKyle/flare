import LabyrinthInventoryItemDefinition from '../../definitions/labyrinth-inventory-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseLabyrinthOracleItemsApiDefinition {
  items: DropdownItem[];
  loadedItems: LabyrinthInventoryItemDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
