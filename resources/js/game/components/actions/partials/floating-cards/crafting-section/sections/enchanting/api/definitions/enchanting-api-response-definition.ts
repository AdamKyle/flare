import EnchantingDataDefinition from './enchanting-data-definition';
import CraftingInventoryCountDefinition from '../../../../shared/api/definitions/crafting-inventory-count-definition';
import CraftingXpDefinition from '../../../../shared/api/definitions/crafting-xp-definition';
export default interface EnchantingApiResponseDefinition {
  affixes: EnchantingDataDefinition;
  skill_xp: CraftingXpDefinition;
  inventory_count?: CraftingInventoryCountDefinition;
  message?: string;
  enchant_succeeded?: boolean;
}
