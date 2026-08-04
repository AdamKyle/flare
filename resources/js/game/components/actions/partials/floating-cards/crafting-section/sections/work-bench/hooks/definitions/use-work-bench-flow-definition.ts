import WorkBenchAlchemySlotDefinition from '../../api/definitions/work-bench-alchemy-slot-definition';
import WorkBenchApiResponseDefinition from '../../api/definitions/work-bench-api-response-definition';
import WorkBenchInventorySlotDefinition from '../../api/definitions/work-bench-inventory-slot-definition';

export default interface UseWorkBenchFlowDefinition {
  data: WorkBenchApiResponseDefinition | null;
  loading: boolean;
  error: string | null;
  mutationError: string | null;
  status: string | null;
  isCraftingDisabled: boolean;
  selectedTargetSlotId: number | null;
  selectedAlchemySlotId: number | null;
  selectedTarget: WorkBenchInventorySlotDefinition | null;
  selectedOil: WorkBenchAlchemySlotDefinition | null;
  cost: number | null;
  submitting: boolean;
  canSubmit: boolean;
  selectTarget: (slotId: number) => void;
  selectOil: (slotId: number) => void;
  submitApply: () => Promise<void>;
}
