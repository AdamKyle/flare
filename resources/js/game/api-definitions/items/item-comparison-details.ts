import { BaseItemDetails } from './base-item-details';

export interface SkillSummaryAdjustment {
  skill_name: string;
  skill_training_bonus_adjustment: number;
  skill_bonus_adjustment: number;
}

export interface ItemAdjustments {
  base_damage_mod_adjustment: number;
  base_healing_mod_adjustment: number;
  base_ac_mod_adjustment: number;
  str_mod_adjustment: number;
  dur_mod_adjustment: number;
  dex_mod_adjustment: number;
  chr_mod_adjustment: number;
  int_mod_adjustment: number;
  agi_mod_adjustment: number;
  focus_mod_adjustment: number;
  resurrection_chance_adjustment: number | null;
  healing_reduction_adjustment: number | null;
  affix_damage_reduction_adjustment: number | null;
  devouring_light_adjustment: number;
  devouring_darkness_adjustment: number;
  ambush_chance_adjustment: number | null;
  counter_chance_adjustment: number | null;
  total_damage_adjustment: number;
  total_defence_adjustment: number;
  total_healing_adjustment: number;
  stackable_adjustment: number;
  non_stacking_adjustment: number;
  irresistible_adjustment: number;
  skill_summary: SkillSummaryAdjustment[];
  spell_evasion_adjustment: number;
}

export interface ComparisonPayload {
  adjustments: ItemAdjustments;
}

export interface ItemComparisonRow {
  position: string;
  equipped_item: BaseItemDetails;
  comparison: ComparisonPayload;
}
