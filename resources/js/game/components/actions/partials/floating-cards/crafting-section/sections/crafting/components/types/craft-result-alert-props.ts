import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface CraftResultAlertProps {
  characterId: number;
  isCrafting: boolean;
  error: string | null;
  successMessage: string | null;
  craftedInventorySlotId: number | null;
  resultPreview: CraftingItemPreviewDefinition | null;
}
