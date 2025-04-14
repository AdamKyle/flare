import React from 'react';

import BaseInventoryItemDefinition from '../../api-definitions/base-inventory-item-definition';

export default interface UsableItemsListProps {
  items: BaseInventoryItemDefinition[];
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
}
