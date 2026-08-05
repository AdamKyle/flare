import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface HolyOilSelectionProps {
  items: DropdownItem[];
  selectedSlotId: number | null;
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  onSearch: (value: string) => void;
  onEndReached: () => void;
  onSelect: (slotId: number) => void;
}
