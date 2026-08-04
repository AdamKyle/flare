import GemTierDefinition from './gem-tier-definition';
import CraftingInventoryCountDefinition from '../../../../shared/api/definitions/crafting-inventory-count-definition';
import CraftingXpDefinition from '../../../../shared/api/definitions/crafting-xp-definition';

export default interface GemCraftingApiResponseDefinition {
  tiers: GemTierDefinition[];
  skill_xp: CraftingXpDefinition;
  inventory_count: CraftingInventoryCountDefinition;
  message?: string;
}
