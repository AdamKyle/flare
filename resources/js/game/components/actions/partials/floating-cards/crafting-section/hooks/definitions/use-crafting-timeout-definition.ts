export default interface UseCraftingTimeoutDefinition {
  isTimeoutActive: boolean;
  isCraftingDisabled: boolean;
  progress: number;
  formattedRemaining: string;
}
