import GemTierDefinition from './gem-tier-definition';
import CharacterGemBagSlotDefinition from '../../../../../../../../../api-definitions/gems/character-gem-bag-slot-definition';
import CharacterGemDefinition from '../../../../../../../../../api-definitions/gems/character-gem-definition';
import CraftingInventoryCountDefinition from '../../../../shared/api/definitions/crafting-inventory-count-definition';
import CraftingXpDefinition from '../../../../shared/api/definitions/crafting-xp-definition';

export default interface GemCraftingApiResponseDefinition {
  tiers: GemTierDefinition[];
  skill_xp: CraftingXpDefinition;
  inventory_count: CraftingInventoryCountDefinition;
  message?: string;
  craft_succeeded?: boolean;
  crafted_gem?: CharacterGemDefinition | null;
  crafted_gem_preview?: CharacterGemBagSlotDefinition | null;
}
