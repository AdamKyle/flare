import React from 'react';

import BaseInventoryItemDefinition from '../../../character-inventory/api-definitions/base-inventory-item-definition';

export default interface GenericItemListProps {
  items: BaseInventoryItemDefinition[];
  is_quest_items: boolean;
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
}
