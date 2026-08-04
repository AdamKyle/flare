import LabyrinthInventoryItemDefinition from '../../api/definitions/labyrinth-inventory-item-definition';
import LabyrinthOracleApiResponseDefinition from '../../api/definitions/labyrinth-oracle-api-response-definition';

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
  selectSource: (id: number) => void;
  selectDestination: (id: number) => void;
  submitTransfer: () => Promise<void>;
}
