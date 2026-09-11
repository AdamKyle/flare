export default interface UseAcknowledgeGemWorldIntroductionDefinition {
  loading: boolean;
  error: string | null;
  acknowledge: () => Promise<boolean>;
}
