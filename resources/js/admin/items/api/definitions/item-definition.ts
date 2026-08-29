import { ItemAlchemyType } from '../../enums/item-alchemy-type';
import { ItemCatalogType } from '../../enums/item-catalog-type';
import { ItemEffectType } from '../../enums/item-effect-type';
import { ItemSpecialtyType } from '../../enums/item-specialty-type';

export interface ItemRelatedIdentityDefinition {
  id: number;
  name: string;
}

export default interface ItemDefinition {
  id: number;
  name: string;
  type: ItemCatalogType;
  can_craft: boolean;
  usable: boolean;
  market_sellable: boolean;
  can_drop: boolean;
  base_damage: number | null;
  base_ac: number | null;
  base_healing: number | null;
  base_damage_mod: number | null;
  base_ac_mod: number | null;
  base_healing_mod: number | null;
  cost: number | null;
  gold_bars_cost: number | null;
  gold_dust_cost: number | null;
  shards_cost: number | null;
  skill_level_required: number | null;
  skill_level_trivial: number | null;
  ambush_chance: number | null;
  ambush_resistance: number | null;
  counter_chance: number | null;
  counter_resistance: number | null;
  item_skill: ItemRelatedIdentityDefinition | null;
  specialty_type: ItemSpecialtyType | null;
  alchemy_type: ItemAlchemyType | null;
  drop_location: ItemRelatedIdentityDefinition | null;
  effect: ItemEffectType | null;
  unlocks_class: ItemRelatedIdentityDefinition | null;
}
