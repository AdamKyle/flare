import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export interface EnchantingAffixSlotProps {
  items: DropdownItem[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  onSearch: (value: string) => void;
  onEndReached: () => void;
}

export default interface EnchantingAffixSelectionProps {
  prefix: EnchantingAffixSlotProps;
  suffix: EnchantingAffixSlotProps;
  onPrefix: (id: number | null) => void;
  onSuffix: (id: number | null) => void;
}
