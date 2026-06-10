import CraftableItemDefinition from '../../definitions/craftable-item-definition';
import CraftingApiResponseDefinition from '../../definitions/crafting-api-response-definition';

export default interface UseCraftItemApiDefinition {
  isCrafting: boolean;
  error: string | null;
  successMessage: string | null;
  craftingResponse: CraftingApiResponseDefinition | null;
  craftedInventorySlotId: number | null;
  craftedItemDetails: CraftableItemDefinition | null;
  craftItem: (craftForNpc: boolean, craftForEvent: boolean) => void;
  clearMessages: () => void;
}
