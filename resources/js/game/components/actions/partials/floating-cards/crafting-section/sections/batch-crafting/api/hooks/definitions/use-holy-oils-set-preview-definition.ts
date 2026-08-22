import HolyOilsSetRequestDefinition from '../../definitions/holy-oils-set-request-definition';
import { HolyOilsSetPreviewDefinition } from '../../definitions/holy-oils-target-preview-definition';

export default interface UseHolyOilsSetPreviewDefinition {
  preview: HolyOilsSetPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (request: HolyOilsSetRequestDefinition) => Promise<void>;
  clearPreview: () => void;
}
