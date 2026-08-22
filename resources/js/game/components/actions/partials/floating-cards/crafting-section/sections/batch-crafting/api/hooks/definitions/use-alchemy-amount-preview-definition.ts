import AlchemyAmountPreviewDefinition from '../../definitions/alchemy-amount-preview-definition';
import AlchemyAmountRequestDefinition from '../../definitions/alchemy-amount-request-definition';

export default interface UseAlchemyAmountPreviewDefinition {
  preview: AlchemyAmountPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (request: AlchemyAmountRequestDefinition) => Promise<void>;
  clearPreview: () => void;
}
