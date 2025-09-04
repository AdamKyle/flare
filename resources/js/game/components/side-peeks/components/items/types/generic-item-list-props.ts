import React from 'react';

import { EquippableItemWithBase } from '../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';

export default interface GenericItemListProps {
  items: EquippableItemWithBase[] | BaseQuestItemDefinition[];
  on_click?: (item_id: number) => void;
  is_quest_items: boolean;
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
  on_selection_change?: (update: ItemSelectedType) => void;
}
