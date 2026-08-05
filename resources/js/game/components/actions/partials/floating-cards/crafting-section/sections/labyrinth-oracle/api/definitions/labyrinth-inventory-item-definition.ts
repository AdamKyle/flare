import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface LabyrinthInventoryItemDefinition {
  id: number;
  affix_name: string;
  preview: CraftingItemPreviewDefinition;
}
