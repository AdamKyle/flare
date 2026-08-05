import EnchantingAffixDefinition from '../../definitions/enchanting-affix-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface UseEnchantingAffixesApiDefinition {
  affixes: DropdownItem[];
  loadedAffixes: EnchantingAffixDefinition[];
  loading: boolean;
  isLoadingMore: boolean;
  canLoadMore: boolean;
  searchText: string;
  setSearchText: (value: string) => void;
  onEndReached: () => void;
}
