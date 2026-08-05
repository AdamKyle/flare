import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import EnchantingApiResponseDefinition from '../../api/definitions/enchanting-api-response-definition';
import UseEnchantingAffixesApiDefinition from '../../api/hooks/definitions/use-enchanting-affixes-api-definition';
import UseEnchantingItemsApiDefinition from '../../api/hooks/definitions/use-enchanting-items-api-definition';
import { EnchantingItemSource } from '../../enums/enchanting-item-source';

export default interface UseEnchantingFlowDefinition {
  data: EnchantingApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  isTimeoutActive: boolean;
  isCraftingDisabled: boolean;
  progress: number;
  formattedRemaining: string;
  hasEventChoice: boolean;
  effectiveSource: EnchantingItemSource | null;
  effectiveSlotId: number | null;
  selectedPrefixId: number | null;
  selectedSuffixId: number | null;
  totalCost: number;
  submitting: boolean;
  canSubmit: boolean;
  lastEnchantSucceeded: boolean | null;
  resultPreview: CraftingItemPreviewDefinition | null;
  itemsApi: UseEnchantingItemsApiDefinition;
  prefixApi: UseEnchantingAffixesApiDefinition;
  suffixApi: UseEnchantingAffixesApiDefinition;
  selectSource: (source: EnchantingItemSource) => void;
  selectSlot: (slotId: number) => void;
  selectPrefix: (prefixId: number | null) => void;
  selectSuffix: (suffixId: number | null) => void;
  submitEnchant: () => Promise<void>;
}
