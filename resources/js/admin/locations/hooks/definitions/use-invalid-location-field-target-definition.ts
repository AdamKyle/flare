export default interface UseInvalidLocationFieldTargetDefinition {
  pending_field_id: string | null;
  clear_pending_field_id: () => void;
  record_attempt: () => void;
}
