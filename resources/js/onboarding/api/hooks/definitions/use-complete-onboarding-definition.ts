export default interface UseCompleteOnboardingDefinition {
  loading: boolean;
  error: string | null;
  complete: () => Promise<boolean>;
}
