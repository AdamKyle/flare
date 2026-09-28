import CharacterGemBagSlotDefinition from '../../../../../../../../api-definitions/gems/character-gem-bag-slot-definition';
import BaseUsableItemDefinition from '../../../../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';

export default interface UseOpenCraftedItemDefinition {
  openCraftedInventoryItem: (characterId: number, slotId: number) => void;
  openCraftedUsableItem: (item: BaseUsableItemDefinition) => void;
  openCraftedGem: (gem: CharacterGemBagSlotDefinition) => void;
}
