import React from 'react';

import BaseInventoryItemDefinition from '../../api-definitions/base-inventory-item-definition';

export default interface UseInfiniteScrollDefinition {
  visibleItems: BaseInventoryItemDefinition[];
  handleScroll: (e: React.UIEvent<HTMLDivElement>) => void;
}
