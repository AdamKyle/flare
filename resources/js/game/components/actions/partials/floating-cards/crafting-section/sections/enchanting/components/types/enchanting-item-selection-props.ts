import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface EnchantingItemSelectionProps {
  items: DropdownItem[];
  selectedSlotId: number | null;
  selectedItemName: string | null;
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  onSearch: (value: string) => void;
  onEndReached: () => void;
  onSelect: (id: number, name: string) => void;
}
