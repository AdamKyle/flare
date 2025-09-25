import { BaseItemDetails } from '../base-item-details';

export default interface BaseUsableItemDefinition extends BaseItemDetails {
  id: number;
  item_id: number;
  slot_id: number;
  damages_kingdoms: boolean;
  kingdom_damage: number | null;
  lasts_for: number | null;
  affects_skill_type: number | null;
  skills: string[];
  increase_skill_bonus_by: number | null;
  increase_skill_training_bonus_by: number | null;
  base_damage_mod_bonus: number | null;
  base_healing_mod_bonus: number | null;
  base_ac_mod_bonus: number | null;
  fight_time_out_mod_bonus: number | null;
  move_time_out_mod_bonus: number | null;
  base_damage_mod: number | null;
  base_ac_mod: number | null;
  base_healing_mod: number | null;
  usable: boolean;
  stat_increase: number;
  holy_level: number | null;
  can_stack: boolean;
  gain_additional_level: boolean;
  xp_bonus: number | null;
  gold_bars_cost: number | null;
  shards_cost: number | null;
  gold_dust_cost: number | null;
  copper_coin_cost: number | null;
}
