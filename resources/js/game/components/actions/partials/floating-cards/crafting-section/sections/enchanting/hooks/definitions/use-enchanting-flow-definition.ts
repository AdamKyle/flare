import EnchantingApiResponseDefinition from '../../api/definitions/enchanting-api-response-definition';
import { EnchantingItemSource } from '../../enums/enchanting-item-source';

export default interface UseEnchantingFlowDefinition {
  data: EnchantingApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  isCraftingDisabled: boolean;
  hasEventChoice: boolean;
  effectiveSource: EnchantingItemSource | null;
  effectiveSlotId: number | null;
  selectedPrefixId: number | null;
  selectedSuffixId: number | null;
  totalCost: number;
  submitting: boolean;
  canSubmit: boolean;
  lastEnchantSucceeded: boolean | null;
  selectSource: (source: EnchantingItemSource) => void;
  selectSlot: (slotId: number) => void;
  selectPrefix: (prefixId: number | null) => void;
  selectSuffix: (suffixId: number | null) => void;
  submitEnchant: () => Promise<void>;
}
