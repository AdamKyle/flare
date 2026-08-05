import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface EnchantingPaginatedItemDefinition {
  slot_id: number;
  item_id: number;
  name: string;
  preview: CraftingItemPreviewDefinition;
}
