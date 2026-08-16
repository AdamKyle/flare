import CraftAmountRequestDefinition from '../../definitions/craft-amount-request-definition';

export default interface UseStartBatchCraftingDefinition {
  starting: boolean;
  error: string | null;
  start: (request: CraftAmountRequestDefinition) => Promise<boolean>;
}
