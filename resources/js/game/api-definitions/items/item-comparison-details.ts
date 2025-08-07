import ItemDetails, { ItemAtonements } from './item-details';

export default interface ItemComparisonDetails {
  details: Detail[];
  atonement: Atonement;
  itemToEquip: ItemDetails;
  type: string;
  slotId: number;
  slotPosition: number | null;
  characterId: number;
  bowEquipped: boolean;
  hammerEquipped: boolean;
  staveEquipped: boolean;
  setEquipped: boolean;
  setIndex: number;
  setName: string;
}

export interface Detail {
  id: number;
  damage_adjustment: number;
  base_damage_adjustment: number;
  base_damage_mod_adjustment: number;
  ac_adjustment: number;
  base_ac_adjustment: number;
  healing_adjustment: number;
  base_healing_adjustment: number;
  str_adjustment: number;
  dur_adjustment: number;
  dex_adjustment: number;
  chr_adjustment: number;
  int_adjustment: number;
  agi_adjustment: number;
  focus_adjustment: number;
  fight_time_out_mod_adjustment: number;
  spell_evasion_adjustment: number;
  res_chance_adjustment: number;
  ambush_chance_adjustment: number;
  ambush_resistance_adjustment: number;
  counter_chance_adjustment: number;
  counter_resistance_adjustment: number;
  str_reduction: number;
  dur_reduction: number;
  dex_reduction: number;
  chr_reduction: number;
  int_reduction: number;
  agi_reduction: number;
  focus_reduction: number;
  reduces_enemy_stats: number;
  steal_life_amount: number;
  entranced_chance: number;
  damage: number;
  class_bonus: number;
  name: string;
  skills: Skill[];
  position: string;
  is_unique: boolean;
  is_mythic: boolean;
  is_cosmic: boolean;
  affix_count: number;
  holy_stacks_applied: number;
  type: string;
}

export interface Skill {
  skill_name: string;
  skill_training_bonus: number;
  skill_bonus: number;
}

export interface Atonement {
  item_atonement: ItemAtonements;
  inventory_atonements: InventoryAtonement[];
}

export interface InventoryAtonement {
  data: ItemAtonements;
  item_name: string;
}
