export default interface UseManageMapMovementErrorStateDefinition {
  errorMessage: string;
  resetErrorMessage: () => void;
  showMessage: (message: string) => void;
}
