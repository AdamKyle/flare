import CraftAndEnchantSetPreviewDefinition from '../../definitions/craft-and-enchant-set-preview-definition';
import CraftAndEnchantSetRequestDefinition from '../../definitions/craft-and-enchant-set-request-definition';

export default interface UseCraftAndEnchantSetPreviewDefinition {
  preview: CraftAndEnchantSetPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (request: CraftAndEnchantSetRequestDefinition) => Promise<void>;
  clearPreview: () => void;
}
