export default interface WorkBenchInventorySlotDefinition {
  id: number;
  item_id: number;
  item: {
    id: number;
    affix_name: string;
    holy_stacks: number;
    holy_stacks_applied: number;
  };
}
