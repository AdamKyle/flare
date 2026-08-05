import GemComparisonApiResponseDefinition from '../../api/definitions/gem-comparison-api-response-definition';
import UseSeerGemsApiDefinition from '../../api/hooks/definitions/use-seer-gems-api-definition';
import UseSeerItemsApiDefinition from '../../api/hooks/definitions/use-seer-items-api-definition';

export default interface UseSeerAttachGemFlowDefinition {
  slotId: number | null;
  gemSlotId: number | null;
  replaceId: number | null;
  comparison: GemComparisonApiResponseDefinition | null;
  comparisonLoading: boolean;
  error: string | null;
  addSubmitting: boolean;
  replaceSubmitting: boolean;
  canReplace: boolean;
  itemsApi: UseSeerItemsApiDefinition;
  gemsApi: UseSeerGemsApiDefinition;
  selectSlot: (slotId: number) => void;
  selectGemSlot: (gemSlotId: number) => void;
  selectReplaceGem: (gemId: number) => void;
  addGem: () => Promise<void>;
  replaceGem: () => Promise<void>;
}
