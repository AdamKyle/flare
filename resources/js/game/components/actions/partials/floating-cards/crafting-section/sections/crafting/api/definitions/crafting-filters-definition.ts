import { CraftableItemSubtype } from './craftable-item-query-definition';

export default interface CraftingFiltersDefinition {
  armour_type?: CraftableItemSubtype;
  item_type?: CraftableItemSubtype;
  [key: string]: CraftableItemSubtype | undefined;
}
