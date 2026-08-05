import TrinketDefinition from './trinket-definition';
import CraftingInventoryCountDefinition from '../../../../shared/api/definitions/crafting-inventory-count-definition';
import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import CraftingXpDefinition from '../../../../shared/api/definitions/crafting-xp-definition';

export default interface TrinketryApiResponseDefinition {
  items: TrinketDefinition[];
  skill_xp: CraftingXpDefinition;
  inventory_count: CraftingInventoryCountDefinition;
  message?: string;
  result_preview?: CraftingItemPreviewDefinition | null;
}
