import InventoryCountDefinition from './inventory-count-definition';

import ElementalAtonementDefinition from 'game-data/api-data-definitions/character/elemental-atonement-definition';
import ResistanceInfoDefinition from 'game-data/api-data-definitions/character/resistance-info-definition';

export default interface CharacterSheetDefinition {
  id: number;
  user_id: number;
  name: string;
  class: string;
  class_id: number;
  class_bonus_chance: number;
  race: string;
  race_id: number;
  level: number;
  max_level: number;
  xp: number;
  xp_next: number;
  to_hit_stat: string;
  ac: number;
  attack: number;
  healing: number;
  health: number;
  resurrection_chance: number;
  gold: number;
  gold_dust: number;
  shards: number;
  copper_coins: number;
  str_raw: number;
  dex_raw: number;
  int_raw: number;
  dur_raw: number;
  agi_raw: number;
  chr_raw: number;
  focus_raw: number;
  str_modded: number;
  dex_modded: number;
  int_modded: number;
  dur_modded: number;
  agi_modded: number;
  chr_modded: number;
  focus_modded: number;
  inventory_count: InventoryCountDefinition;
  resistance_info: ResistanceInfoDefinition;
  elemental_atonements: ElementalAtonementDefinition;
}
