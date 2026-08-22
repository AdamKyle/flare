export default interface UseOpenBatchCraftingSetDefinition {
  openBatchCraftingSet: (
    set_id: number,
    set_name: string,
    item_name?: string
  ) => void;
}
