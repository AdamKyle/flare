import React from 'react';

import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { ItemTypeToView } from '../enums/item-type-to-view';

export default interface GenericItemListProps {
  items: EquippableItemWithBase[] | BaseQuestItemDefinition[];
  items_view_type: ItemTypeToView;
  on_click?: (typeOfItem: ItemTypeToView, itemId: number) => void;
  is_quest_items: boolean;
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
}
