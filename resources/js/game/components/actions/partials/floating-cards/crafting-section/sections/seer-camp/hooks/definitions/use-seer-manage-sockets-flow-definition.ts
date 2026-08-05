import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import SeerItemDefinition from '../../api/definitions/seer-item-definition';
import UseSeerItemsApiDefinition from '../../api/hooks/definitions/use-seer-items-api-definition';

export default interface UseSeerManageSocketsFlowDefinition {
  selectedItem: SeerItemDefinition | null;
  itemsApi: UseSeerItemsApiDefinition;
  submitting: boolean;
  error: string | null;
  canSubmit: boolean;
  resultPreview: CraftingItemPreviewDefinition | null;
  selectItem: (slotId: number) => void;
  submit: () => Promise<void>;
}
