export default interface UseActiveBoonsActionsDefinition {
  successMessage: string | null;
  mutationError: string | null;
  fillingBoonId: number | null;
  removingBoonId: number | null;
  fillUpBoon: (boonId: number) => Promise<void>;
  removeBoon: (boonId: number) => Promise<void>;
}
