import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';

export default interface CraftableItemDefinition {
  id: number;
  cost: number;
  crafting_type: string;
  default_position: string | null;
  skill_level_required: number;
  skill_level_trivial: number;
  preview: CraftingItemPreviewDefinition;
}
