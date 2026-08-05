import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface QueenInventorySlotDefinition {
  slot_id: number;
  item_id: number;
  preview: CraftingItemPreviewDefinition;
}
