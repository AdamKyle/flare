export default interface UseUseAlchemyItemApiDefinition {
  using: boolean;
  error: string | null;
  successMessage: string | null;
  useAlchemyItem: (slotId: number, useAll: boolean) => Promise<void>;
}
