export default interface WorkBenchCostLookupDefinition {
  [inventorySlotId: number]: {
    [alchemySlotId: number]: number;
  };
}
