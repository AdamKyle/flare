import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import TrinketDefinition from '../../api/definitions/trinket-definition';
import TrinketryApiResponseDefinition from '../../api/definitions/trinketry-api-response-definition';
import UseTrinketryItemsApiDefinition from '../../api/hooks/definitions/use-trinketry-items-api-definition';

export default interface UseTrinketryFlowDefinition {
  data: TrinketryApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  selectedItem: TrinketDefinition | null;
  crafting: boolean;
  canCraft: boolean;
  isTimeoutActive: boolean;
  isCraftingDisabled: boolean;
  progress: number;
  formattedRemaining: string;
  itemsApi: UseTrinketryItemsApiDefinition;
  resultPreview: CraftingItemPreviewDefinition | null;
  selectItem: (itemId: number) => void;
  craftItem: () => Promise<void>;
}
