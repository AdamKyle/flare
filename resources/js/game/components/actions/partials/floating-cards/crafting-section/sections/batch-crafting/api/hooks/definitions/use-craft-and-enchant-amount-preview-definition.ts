import CraftAndEnchantAmountPreviewDefinition from '../../definitions/craft-and-enchant-amount-preview-definition';
import CraftAndEnchantAmountRequestDefinition from '../../definitions/craft-and-enchant-amount-request-definition';

export default interface UseCraftAndEnchantAmountPreviewDefinition {
  preview: CraftAndEnchantAmountPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (
    request: CraftAndEnchantAmountRequestDefinition
  ) => Promise<void>;
  clearPreview: () => void;
}
