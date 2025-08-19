import React from 'react';

import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import BaseInventoryItemDefinition from '../../../character-inventory/api-definitions/base-inventory-item-definition';
import { ItemTypeToView } from '../enums/item-type-to-view';

export default interface GenericItemListProps {
  items: BaseInventoryItemDefinition[] | BaseQuestItemDefinition[];
  items_view_type: ItemTypeToView;
  on_click?: (typeOfItem: ItemTypeToView, itemId: number) => void;
  is_quest_items: boolean;
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
}
