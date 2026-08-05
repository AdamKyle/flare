import WorkBenchRangeDefinition from '../../api/definitions/work-bench-range-definition';

export default interface HolyOilCostSummaryProps {
  currentStacks: number;
  resultingStacks: number;
  maximumStacks: number;
  goldDustCost: number | null;
  statBonusRange: WorkBenchRangeDefinition | null;
  devoidanceRange: WorkBenchRangeDefinition | null;
}
