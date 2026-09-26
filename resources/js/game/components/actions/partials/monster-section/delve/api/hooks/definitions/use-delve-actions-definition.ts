export default interface UseDelveActionsDefinition {
  stopping: boolean;
  dismissing: boolean;
  error: string | null;
  stop: () => Promise<boolean>;
  dismiss: () => Promise<boolean>;
}
