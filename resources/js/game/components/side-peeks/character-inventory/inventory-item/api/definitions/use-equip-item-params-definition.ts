export default interface UseEquipItemParamsDefinition {
  character_id: number;
  on_success: (successMessage: string) => void;
}
