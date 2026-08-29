/**
 * Matches App\Game\Core\Items\Transformers\Api\UsableItemTransformer::transform()
 * exactly — the same canonical transformer the player-facing usable Item
 * presentation uses. `slot_id` is null for a bare catalog Item because the
 * transformer already supports being called with a bare `Item` instead of an
 * inventory slot.
 */
export default interface AdminUsableItemPresentationDefinition {
  item_id: number;
  slot_id: number | null;
  name: string;
  type: string;
  description: string;
  damages_kingdoms: boolean;
  kingdom_damage: number | null;
  lasts_for: number | null;
  affects_skill_type: number | null;
  skills: string[];
  increase_skill_bonus_by: number | null;
  increase_skill_training_bonus_by: number | null;
  fight_time_out_mod_bonus: number | null;
  move_time_out_mod_bonus: number | null;
  base_damage_mod: number | null;
  base_ac_mod: number | null;
  base_healing_mod: number | null;
  usable: boolean;
  stat_increase: number | null;
  holy_level: number | null;
  can_stack: boolean;
  gain_additional_level: boolean;
  xp_bonus: number | null;
  gold_dust_cost: number | null;
  shards_cost: number | null;
  gold_bars_cost: number | null;
}
