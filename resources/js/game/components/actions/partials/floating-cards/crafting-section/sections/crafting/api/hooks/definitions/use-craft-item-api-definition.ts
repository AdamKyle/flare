import CraftingItemPreviewDefinition from '../../../../../shared/api/definitions/crafting-item-preview-definition';
import CraftingApiResponseDefinition from '../../definitions/crafting-api-response-definition';

export default interface UseCraftItemApiDefinition {
  isCrafting: boolean;
  error: string | null;
  successMessage: string | null;
  craftingResponse: CraftingApiResponseDefinition | null;
  craftedInventorySlotId: number | null;
  resultPreview: CraftingItemPreviewDefinition | null;
  craftItem: (craftForNpc: boolean, craftForEvent: boolean) => void;
  clearMessages: () => void;
}
