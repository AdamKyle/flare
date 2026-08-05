export interface CraftingItemPreviewAffixDefinition {
  id: number;
  name: string;
}

export default interface CraftingItemPreviewDefinition {
  item_id: number;
  inventory_slot_id: number | null;
  name: string;
  description: string | null;
  type: string;
  base_damage: number;
  base_damage_mod: number | null;
  base_ac: number;
  base_ac_mod: number | null;
  base_healing: number;
  base_healing_mod: number | null;
  str_modifier: number | null;
  dur_modifier: number | null;
  dex_modifier: number | null;
  chr_modifier: number | null;
  int_modifier: number | null;
  agi_modifier: number | null;
  focus_modifier: number | null;
  ambush_chance: number;
  ambush_resistance_chance: number;
  counter_chance: number;
  counter_resistance_chance: number;
  is_mythic: boolean;
  is_cosmic: boolean;
  is_unique: boolean;
  affix_count: number;
  holy_stacks: number;
  holy_stacks_applied: number;
  socket_count: number;
  usable: boolean;
  holy_level: number | null;
  damages_kingdoms: boolean;
  item_prefix: CraftingItemPreviewAffixDefinition | null;
  item_suffix: CraftingItemPreviewAffixDefinition | null;
}
