import BaseGemDetails from '../../../../../../../../../api-definitions/items/base-gem-details';
import GemCraftingApiResponseDefinition from '../../api/definitions/gem-crafting-api-response-definition';
import GemTierDefinition from '../../api/definitions/gem-tier-definition';

export default interface UseGemCraftingFlowDefinition {
  characterId: number;
  data: GemCraftingApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  selectedTier: number | null;
  selectedTierData: GemTierDefinition | null;
  crafting: boolean;
  canCraft: boolean;
  isTimeoutActive: boolean;
  isCraftingDisabled: boolean;
  progress: number;
  formattedRemaining: string;
  craftSucceeded: boolean;
  craftedGemPreview: BaseGemDetails | null;
  selectTier: (tier: number) => void;
  craftGem: () => Promise<void>;
}
