export type BackpackRarityFilter =
  'unique' | 'mythic' | 'cosmic' | 'single-enchanted' | 'dual-enchanted';

export default interface BackpackFilterDefinition extends Record<
  string,
  unknown
> {
  item_types?: string[];
  rarity?: BackpackRarityFilter;
}
