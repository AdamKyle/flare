import SeerGemDefinition from '../../definitions/seer-gem-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseSeerGemsApiDefinition {
  items: DropdownItem[];
  loadedItems: SeerGemDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
