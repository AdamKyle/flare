import type { UIEvent } from 'react';

import CraftableItemDefinition from '../../api/definitions/craftable-item-definition';

export default interface CraftItemListProps {
  items: CraftableItemDefinition[];
  selectedItem: CraftableItemDefinition | null;
  loadingMore: boolean;
  handle_scroll: (event: UIEvent<HTMLDivElement>) => void;
  onSelect: (item: CraftableItemDefinition) => void;
}
