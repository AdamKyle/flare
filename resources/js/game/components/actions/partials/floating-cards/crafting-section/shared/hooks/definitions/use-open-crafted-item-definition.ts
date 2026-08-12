import BaseGemDetails from '../../../../../../../../api-definitions/items/base-gem-details';
import BaseUsableItemDefinition from '../../../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface UseOpenCraftedItemDefinition {
  openCraftedInventoryItem: (characterId: number, slotId: number) => void;
  openCraftedUsableItem: (item: BaseUsableItemDefinition) => void;
  openCraftedGem: (gem: BaseGemDetails) => void;
}
