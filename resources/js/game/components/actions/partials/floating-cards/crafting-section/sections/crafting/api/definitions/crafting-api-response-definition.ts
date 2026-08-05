import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import CraftableItemDefinition from './craftable-item-definition';
import CraftingInventoryCountDefinition from '../../../../shared/api/definitions/crafting-inventory-count-definition';
import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import CraftingXpDefinition from '../../../../shared/api/definitions/crafting-xp-definition';

export default interface CraftingApiResponseDefinition extends PaginatedApiResponseDefinition<
  CraftableItemDefinition[]
> {
  xp: CraftingXpDefinition;
  show_craft_for_npc: boolean;
  show_craft_for_event: boolean;
  inventory_count: CraftingInventoryCountDefinition;
  crafted_item?: boolean;
  crafted_inventory_slot_id?: number | null;
  result_preview?: CraftingItemPreviewDefinition | null;
}
