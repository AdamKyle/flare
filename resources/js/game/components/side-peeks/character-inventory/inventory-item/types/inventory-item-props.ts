export default interface InventoryItemProps {
  slot_id: number;
  character_id: number;
  on_action: (successMessage: string) => void;
}
