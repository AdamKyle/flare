import React from 'react';

import BaseUsableItemDefinition from '../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import ActiveBoonDefinition from '../../../../actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/definitions/active-boon-definition';

export default interface UsableItemsListProps {
  items: BaseUsableItemDefinition[];
  on_scroll_to_end: (e: React.UIEvent<HTMLDivElement>) => void;
  on_item_clicked: (itemId: number) => void;
  active_boons: ActiveBoonDefinition[];
  using_slot_id: number | null;
  character_id: number;
  on_use_one: (slotId: number) => void;
  on_use_quantity: (slotId: number, quantity: number) => void;
  on_use_all: (slotId: number) => void;
  on_gem_scroll_activated: () => void;
}
