export default interface InventorySetOptionDefinition {
  set_id: number;
  name: string | null;
  equipped: boolean;
  is_batch_crafting_set: boolean;
  max_slots: number | null;
  current_slots: number;
  remaining_slots: number | null;
  set_number: number | null;
  display_name: string;
}
