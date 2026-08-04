import GemCraftingApiResponseDefinition from '../../api/definitions/gem-crafting-api-response-definition';
import GemTierDefinition from '../../api/definitions/gem-tier-definition';

export default interface UseGemCraftingFlowDefinition {
  data: GemCraftingApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  selectedTier: number | null;
  selectedTierData: GemTierDefinition | null;
  crafting: boolean;
  canCraft: boolean;
  selectTier: (tier: number) => void;
  craftGem: () => Promise<void>;
}
