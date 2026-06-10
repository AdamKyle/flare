import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import CraftableItemDefinition from './craftable-item-definition';
import CraftingInventoryCountDefinition from './crafting-inventory-count-definition';
import CraftingXpDefinition from './crafting-xp-definition';

export default interface CraftingApiResponseDefinition extends PaginatedApiResponseDefinition<
  CraftableItemDefinition[]
> {
  xp: CraftingXpDefinition;
  show_craft_for_npc: boolean;
  show_craft_for_event: boolean;
  inventory_count: CraftingInventoryCountDefinition;
  crafted_item?: boolean;
}
