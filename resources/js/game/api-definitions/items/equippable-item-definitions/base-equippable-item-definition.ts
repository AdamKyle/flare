import { BaseItemDetails } from '../base-item-details';
import { AtonementsDefinition } from './atonements-definition';
import { HolyStackDefinition } from './holy-stack-definition';
import ItemAffixDefinition from './item-affix-definition';
import SkillSummaryDefinition from './skill-summary-definition';
import { SocketDefinition } from './socket-definition';

export default interface EquippableItemDefinition {
  item_id: number;
  slot_id: number;

  raw_damage: number | null;
  raw_ac: number | null;
  raw_healing: number | null;

  base_damage: number | null;
  base_ac: number | null;
  base_healing: number | null;

  base_damage_mod: number | string | null;
  base_ac_mod: number | string | null;
  base_healing_mod: number | string | null;

  base_damage_mod_bonus: number;
  base_healing_mod_bonus: number;
  base_ac_mod_bonus: number;

  affix_damage_reduction: number;

  str_modifier: number | null;
  dur_modifier: number | null;
  int_modifier: number | null;
  dex_modifier: number | null;
  chr_modifier: number | null;
  agi_modifier: number | null;
  focus_modifier: number | null;

  default_position: string | null;

  skill_summary: SkillSummaryDefinition[] | null;

  item_prefix: ItemAffixDefinition | null;
  item_suffix: ItemAffixDefinition | null;

  crafting_type: string;
  skill_level_req: number;
  skill_level_trivial: number;

  cost: number;

  fight_time_out_mod_bonus: number;
  move_time_out_mod_bonus: number;

  holy_stacks: number;
  applied_stacks: HolyStackDefinition[];
  holy_stack_devouring_darkness: number;
  holy_stack_stat_bonus: number;

  ambush_chance: number;
  ambush_resistance_chance: number;
  counter_chance: number;
  counter_resistance_chance: number;

  devouring_light: number;
  devouring_darkness: number;

  total_stackable_affix_damage: number | null;
  total_non_stacking_affix_damage: number | null;
  total_irresistible_affix_damage: number | null;

  sockets: SocketDefinition[];
  socket_amount: number;

  item_atonements: AtonementsDefinition;

  spell_evasion: number;
  healing_reduction: number;
  resurrection_chance: number;
}

export type EquippableItemWithBase = EquippableItemDefinition & BaseItemDetails;
