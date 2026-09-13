export default interface UseReviveCharacterDefinition {
  loading: boolean;
  error: string | null;
  revive: () => Promise<boolean>;
}
