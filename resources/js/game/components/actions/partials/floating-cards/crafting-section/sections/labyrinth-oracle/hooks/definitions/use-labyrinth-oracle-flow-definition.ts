import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import LabyrinthInventoryItemDefinition from '../../api/definitions/labyrinth-inventory-item-definition';
import LabyrinthOracleApiResponseDefinition from '../../api/definitions/labyrinth-oracle-api-response-definition';
import UseLabyrinthOracleItemsApiDefinition from '../../api/hooks/definitions/use-labyrinth-oracle-items-api-definition';

export default interface UseLabyrinthOracleFlowDefinition {
  characterId: number;
  data: LabyrinthOracleApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  sourceId: number | null;
  destinationId: number | null;
  sourceItem: LabyrinthInventoryItemDefinition | null;
  destinationItem: LabyrinthInventoryItemDefinition | null;
  hasEnoughItemsToTransfer: boolean;
  submitting: boolean;
  canSubmit: boolean;
  sourceResultPreview: CraftingItemPreviewDefinition | null;
  destinationResultPreview: CraftingItemPreviewDefinition | null;
  itemsApi: UseLabyrinthOracleItemsApiDefinition;
  selectSource: (id: number) => void;
  selectDestination: (id: number) => void;
  submitTransfer: () => Promise<void>;
}
