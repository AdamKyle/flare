export default interface UseMoveItemToSetRequestParams {
  character_id: number;
  inventory_set_id: number;
  inventory_slot_id: number;
  on_success: (message: string) => void;
}
