export default interface UseManageConjureButtonStateDefinition {
  isConjureEnabled: boolean;
  manageConjureButtonState: (enabled: boolean) => void;
}
