import { BackpackRarityFilter } from '../enums/backpack-rarity-filter';

export default interface BackpackFilterDefinition extends Record<
  string,
  unknown
> {
  item_types?: string[];
  rarity?: BackpackRarityFilter;
}
