export default interface UseGemWorldMutationDefinition {
  loading: boolean;
  error: string | null;
  action: () => Promise<boolean>;
}
