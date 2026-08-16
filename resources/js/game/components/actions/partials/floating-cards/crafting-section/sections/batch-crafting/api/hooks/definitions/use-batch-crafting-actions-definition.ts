export default interface UseBatchCraftingActionsDefinition {
  cancelling: boolean;
  dismissing: boolean;
  acknowledging: boolean;
  error: string | null;
  cancel: () => Promise<boolean>;
  dismiss: () => Promise<boolean>;
  acknowledgeInfo: () => Promise<boolean>;
}
