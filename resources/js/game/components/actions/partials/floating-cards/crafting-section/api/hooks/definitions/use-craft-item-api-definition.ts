import CraftingApiResponseDefinition from '../../definitions/crafting-api-response-definition';

export default interface UseCraftItemApiDefinition {
  isCrafting: boolean;
  error: string | null;
  successMessage: string | null;
  craftingResponse: CraftingApiResponseDefinition | null;
  craftItem: (craftForNpc: boolean, craftForEvent: boolean) => void;
  clearMessages: () => void;
}
