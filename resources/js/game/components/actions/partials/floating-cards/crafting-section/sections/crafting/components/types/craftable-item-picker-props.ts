import type { UIEvent } from 'react';

import CraftableItemDefinition from '../../api/definitions/craftable-item-definition';

export default interface CraftableItemPickerProps {
  searchInput: string;
  items: CraftableItemDefinition[];
  selectedItem: CraftableItemDefinition | null;
  loading: boolean;
  loadingMore: boolean;
  onSearch: (value: string) => void;
  onScroll: (event: UIEvent<HTMLDivElement>) => void;
  onSelect: (item: CraftableItemDefinition) => void;
}
