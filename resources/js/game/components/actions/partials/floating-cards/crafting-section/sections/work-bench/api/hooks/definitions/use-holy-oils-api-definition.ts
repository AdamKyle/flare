import WorkBenchAlchemySlotDefinition from '../../definitions/work-bench-alchemy-slot-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseHolyOilsApiDefinition {
  items: DropdownItem[];
  loadedItems: WorkBenchAlchemySlotDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
