import AlchemyApiResponseDefinition from '../../api/definitions/alchemy-api-response-definition';
import AlchemyItemDefinition from '../../api/definitions/alchemy-item-definition';

export default interface UseAlchemyFlowDefinition {
  data: AlchemyApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  selectedItem: AlchemyItemDefinition | null;
  transmuting: boolean;
  canTransmute: boolean;
  selectItem: (itemId: number) => void;
  transmuteItem: () => Promise<void>;
}
