export default interface UseUseCurrencyCacheApiDefinition {
  usingSlotId: number | null;
  error: string | null;
  successMessage: string | null;
  useCurrencyCache: (slotId: number) => Promise<void>;
}
