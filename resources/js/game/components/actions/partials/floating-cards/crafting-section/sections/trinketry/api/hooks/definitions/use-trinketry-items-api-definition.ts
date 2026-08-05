import TrinketDefinition from '../../definitions/trinket-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseTrinketryItemsApiDefinition {
  items: DropdownItem[];
  loadedItems: TrinketDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
