import AlchemyItemDefinition from '../../definitions/alchemy-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseAlchemyItemsApiDefinition {
  items: DropdownItem[];
  loadedItems: AlchemyItemDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
