import CraftingItemPreviewDefinition from '../../api/definitions/crafting-item-preview-definition';

export default interface CraftingItemPreviewProps {
  item: CraftingItemPreviewDefinition;
  display_name?: string;
  on_name_click?: () => void;
}
