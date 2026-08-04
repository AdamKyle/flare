import TrinketDefinition from '../../api/definitions/trinket-definition';
import TrinketryApiResponseDefinition from '../../api/definitions/trinketry-api-response-definition';

export default interface UseTrinketryFlowDefinition {
  data: TrinketryApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  selectedItem: TrinketDefinition | null;
  crafting: boolean;
  canCraft: boolean;
  selectItem: (itemId: number) => void;
  craftItem: () => Promise<void>;
}
