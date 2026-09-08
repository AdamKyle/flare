export default interface UseUseManyAlchemyItemsApiDefinition {
  using: boolean;
  error: string | null;
  successMessage: string | null;
  useManyItems: (itemsToUse: number[]) => Promise<void>;
}
