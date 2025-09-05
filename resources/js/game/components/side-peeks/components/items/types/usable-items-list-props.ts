import React from 'react';

import BaseUsableItemDefinition from '../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface UsableItemsListProps {
  items: BaseUsableItemDefinition[];
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
}
