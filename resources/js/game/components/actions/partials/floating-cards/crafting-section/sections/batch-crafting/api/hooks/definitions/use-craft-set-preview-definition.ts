import CraftSetPreviewDefinition from '../../definitions/craft-set-preview-definition';
import CraftSetRequestDefinition from '../../definitions/craft-set-request-definition';

export default interface UseCraftSetPreviewDefinition {
  preview: CraftSetPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (request: CraftSetRequestDefinition) => Promise<void>;
  clearPreview: () => void;
}
