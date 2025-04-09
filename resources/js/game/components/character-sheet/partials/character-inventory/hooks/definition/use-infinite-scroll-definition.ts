import React from 'react';

import BaseGemDetails from '../../../../../../api-definitions/items/base-gem-details';
import BaseInventoryItemDefinition from '../../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface UseInfiniteScrollDefinition {
  visibleItems: BaseInventoryItemDefinition[] | BaseGemDetails[];
  handleScroll: (e: React.UIEvent<HTMLDivElement>) => void;
}
