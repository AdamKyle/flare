import AlchemyApiResponseDefinition from '../../api/definitions/alchemy-api-response-definition';
import AlchemyItemDefinition from '../../api/definitions/alchemy-item-definition';
import AlchemyResultDefinition from '../../api/definitions/alchemy-result-definition';
import UseAlchemyItemsApiDefinition from '../../api/hooks/definitions/use-alchemy-items-api-definition';

export default interface UseAlchemyFlowDefinition {
  characterId: number;
  data: AlchemyApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  selectedItem: AlchemyItemDefinition | null;
  transmuting: boolean;
  canTransmute: boolean;
  isTimeoutActive: boolean;
  isCraftingDisabled: boolean;
  progress: number;
  formattedRemaining: string;
  itemsApi: UseAlchemyItemsApiDefinition;
  alchemyResult: AlchemyResultDefinition | null;
  selectItem: (itemId: number) => void;
  transmuteItem: () => Promise<void>;
}
