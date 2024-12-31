import InventoryCountDefinition from './inventory-count-definition';

export default interface CharacterSheetDefinition {
  id: number;
  user_id: number;
  name: string;
  class: string;
  class_id: number;
  class_bonus_chance: number;
  race: string;
  race_id: number;
  level: string;
  max_level: string;
  xp: number;
  xp_next: number;
  to_hit_stat: string;
  ac: number;
  attack: number;
  health: number;
  resurrection_chance: number;
  gold: string;
  gold_dust: string;
  shards: string;
  copper_coins: string;
  str_modded: number;
  dex_modded: number;
  int_modded: number;
  dur_modded: number;
  agi_modded: number;
  chr_modded: number;
  focus_modded: number;
  inventory_count: InventoryCountDefinition;
}
