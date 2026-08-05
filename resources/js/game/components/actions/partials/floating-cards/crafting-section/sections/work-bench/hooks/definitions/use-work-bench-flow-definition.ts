import CraftingItemPreviewDefinition from '../../../../shared/api/definitions/crafting-item-preview-definition';
import WorkBenchAlchemySlotDefinition from '../../api/definitions/work-bench-alchemy-slot-definition';
import WorkBenchApiResponseDefinition from '../../api/definitions/work-bench-api-response-definition';
import WorkBenchInventorySlotDefinition from '../../api/definitions/work-bench-inventory-slot-definition';
import WorkBenchRangeDefinition from '../../api/definitions/work-bench-range-definition';
import UseHolyOilsApiDefinition from '../../api/hooks/definitions/use-holy-oils-api-definition';
import UseWorkBenchItemsApiDefinition from '../../api/hooks/definitions/use-work-bench-items-api-definition';

export default interface UseWorkBenchFlowDefinition {
  data: WorkBenchApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  isTimeoutActive: boolean;
  isCraftingDisabled: boolean;
  progress: number;
  formattedRemaining: string;
  selectedTargetSlotId: number | null;
  selectedAlchemySlotId: number | null;
  selectedTarget: WorkBenchInventorySlotDefinition | null;
  selectedOil: WorkBenchAlchemySlotDefinition | null;
  cost: number | null;
  statBonusRange: WorkBenchRangeDefinition | null;
  devoidanceRange: WorkBenchRangeDefinition | null;
  resultPreview: CraftingItemPreviewDefinition | null;
  submitting: boolean;
  canSubmit: boolean;
  itemsApi: UseWorkBenchItemsApiDefinition;
  oilsApi: UseHolyOilsApiDefinition;
  selectTarget: (slotId: number) => void;
  selectOil: (slotId: number) => void;
  submitApply: () => Promise<void>;
}
