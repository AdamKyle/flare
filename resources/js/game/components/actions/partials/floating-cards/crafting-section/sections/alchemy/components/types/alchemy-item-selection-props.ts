import AlchemyItemDefinition from '../../api/definitions/alchemy-item-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface AlchemyItemSelectionProps {
  items: DropdownItem[];
  loadedItems: AlchemyItemDefinition[];
  selectedItemId: number | null;
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  onSearch: (value: string) => void;
  onEndReached: () => void;
  onSelect: (itemId: number) => void;
}
