export default interface SetOptionDefinition {
  name: string;
  equippable: boolean;
  set_id: number;
  equipped: boolean;
  is_equippable: boolean;
  is_batch_crafting_set: boolean;
  max_slots: number | null;
  current_slots: number;
  remaining_slots: number;
  can_empty: boolean;
  empty_disabled_reason: string | null;
}
