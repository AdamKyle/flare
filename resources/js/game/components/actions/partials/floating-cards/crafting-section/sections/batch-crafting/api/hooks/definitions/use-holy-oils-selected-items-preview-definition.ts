import HolyOilsSelectedItemsRequestDefinition from '../../definitions/holy-oils-selected-items-request-definition';
import { HolyOilsSelectedItemsPreviewDefinition } from '../../definitions/holy-oils-target-preview-definition';

export default interface UseHolyOilsSelectedItemsPreviewDefinition {
  preview: HolyOilsSelectedItemsPreviewDefinition | null;
  loading: boolean;
  error: string | null;
  fetchPreview: (
    request: HolyOilsSelectedItemsRequestDefinition
  ) => Promise<void>;
  clearPreview: () => void;
}
