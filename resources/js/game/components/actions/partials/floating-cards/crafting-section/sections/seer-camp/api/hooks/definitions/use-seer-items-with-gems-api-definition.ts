import SeerItemDefinition from '../../definitions/seer-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseSeerItemsWithGemsApiDefinition {
  items: DropdownItem[];
  loadedItems: SeerItemDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
