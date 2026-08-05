import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface TrinketSelectionProps {
  items: DropdownItem[];
  selectedItemId: number | null;
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  onSearch: (value: string) => void;
  onEndReached: () => void;
  onSelect: (itemId: number) => void;
}
