export default interface UseStopExplorationDefinition {
  loading: boolean;
  error: string | null;
  stop: () => Promise<boolean>;
}
