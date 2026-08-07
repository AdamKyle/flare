import CraftingApiResponseDefinition from '../../definitions/crafting-api-response-definition';

export default interface UseCraftItemApiDefinition {
  isCrafting: boolean;
  error: string | null;
  craftingResponse: CraftingApiResponseDefinition | null;
  craftedInventorySlotId: number | null;
  craftItem: (craftForNpc: boolean, craftForEvent: boolean) => void;
  clearResult: () => void;
}
