import WorkBenchInventorySlotDefinition from '../../api/definitions/work-bench-inventory-slot-definition';

export default interface WorkBenchItemSelectionProps {
  items: WorkBenchInventorySlotDefinition[];
  selectedSlotId: number | null;
  onSelect: (slotId: number) => void;
}
