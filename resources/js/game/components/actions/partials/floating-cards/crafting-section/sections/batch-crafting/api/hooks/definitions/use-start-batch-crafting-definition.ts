import BatchCraftingStartRequestDefinition from '../../definitions/batch-crafting-start-request-definition';

export default interface UseStartBatchCraftingDefinition {
  starting: boolean;
  error: string | null;
  start: (request: BatchCraftingStartRequestDefinition) => Promise<boolean>;
}
