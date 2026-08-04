import WorkBenchAlchemySlotDefinition from './work-bench-alchemy-slot-definition';
import WorkBenchCostLookupDefinition from './work-bench-cost-lookup-definition';
import WorkBenchInventorySlotDefinition from './work-bench-inventory-slot-definition';

export default interface WorkBenchApiResponseDefinition {
  items: WorkBenchInventorySlotDefinition[];
  alchemy_items: WorkBenchAlchemySlotDefinition[];
  costs: WorkBenchCostLookupDefinition;
  message?: string;
}
