import AlchemyItemDefinition from './alchemy-item-definition';
import AlchemyResultDefinition from './alchemy-result-definition';
import CraftingInventoryCountDefinition from '../../../../shared/api/definitions/crafting-inventory-count-definition';
import CraftingXpDefinition from '../../../../shared/api/definitions/crafting-xp-definition';

export default interface AlchemyApiResponseDefinition {
  items: AlchemyItemDefinition[];
  skill_xp: CraftingXpDefinition;
  inventory_count: CraftingInventoryCountDefinition;
  message?: string;
  alchemy_result?: AlchemyResultDefinition | null;
}
