import {
  CraftableItemCraftingType,
  CraftableItemSubtype,
} from '../../definitions/craftable-item-query-definition';

export default interface UseCraftableItemsApiParams {
  characterId: number;
  selectedType: CraftableItemCraftingType | null;
  armourType: CraftableItemSubtype | null;
  itemType: CraftableItemSubtype | null;
}
