import WorkBenchAlchemySlotDefinition from '../../api/definitions/work-bench-alchemy-slot-definition';

export default interface HolyOilSelectionProps {
  oils: WorkBenchAlchemySlotDefinition[];
  selectedSlotId: number | null;
  onSelect: (slotId: number) => void;
}
