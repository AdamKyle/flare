export default interface UseListItemOnMarketRequestParamsDefinition {
  character_id: number;
  list_for: number;
  slot_id: number;
  on_success: (message: string) => void;
}
