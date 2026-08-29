/**
 * The smallest permission-neutral factual usable/alchemy Item shape rendered
 * by `ItemMetaSection` + `UsableItemEffects` and their partials. Deliberately
 * narrower than `BaseUsableItemDefinition` (which also carries inventory-slot
 * fields such as `slot_id` that this factual presentation never reads), so
 * both the player-facing inventory usable Item and the permission-neutral
 * Admin usable Item presentation can share this exact rendering without
 * fabricating inventory-slot state.
 */
export default interface UsableItemFactualDefinition {
  name: string;
  description: string;
  type: string;
  usable: boolean;
  can_stack: boolean;
  lasts_for: number | null;
  gain_additional_level: boolean;
  xp_bonus: number | null;
  stat_increase: number | null;
  base_damage_mod: number | null;
  base_healing_mod: number | null;
  base_ac_mod: number | null;
  fight_time_out_mod_bonus: number | null;
  move_time_out_mod_bonus: number | null;
  increase_skill_bonus_by: number | null;
  increase_skill_training_bonus_by: number | null;
  skills: string[];
  damages_kingdoms: boolean;
  kingdom_damage: number | null;
  holy_level: number | null;
}
