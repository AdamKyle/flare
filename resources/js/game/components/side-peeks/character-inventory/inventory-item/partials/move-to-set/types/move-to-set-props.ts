export default interface MoveToSetProps {
  character_id: number;
  item_slot_id: number;
  on_action: (message: string) => void;
}
