import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface TransferItemSelectionProps {
  items: DropdownItem[];
  sourceId: number | null;
  destinationId: number | null;
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  onSearch: (value: string) => void;
  onEndReached: () => void;
  onSource: (id: number) => void;
  onDestination: (id: number) => void;
}
