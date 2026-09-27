import { CurrencyCacheTypeValue } from '../enums/currency-cache-type-labels';

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
  currency_cache_type?: CurrencyCacheTypeValue | null;
  cache_amount?: number | null;
}
