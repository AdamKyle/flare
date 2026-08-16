import BatchCraftingPreviewDefinition from '../../definitions/batch-crafting-preview-definition';
import CraftAmountRequestDefinition from '../../definitions/craft-amount-request-definition';

export default interface UseBatchCraftingPreviewDefinition {
  preview: BatchCraftingPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (request: CraftAmountRequestDefinition) => Promise<void>;
  clearPreview: () => void;
}
