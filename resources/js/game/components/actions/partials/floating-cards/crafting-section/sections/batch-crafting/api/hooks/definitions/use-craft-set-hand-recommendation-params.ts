import {
  CraftableItemCraftingType,
  CraftableItemSubtype,
} from '../../../../crafting/api/definitions/craftable-item-query-definition';

export default interface UseCraftSetHandRecommendationParams {
  characterId: number;
  handType: CraftableItemCraftingType | CraftableItemSubtype | null;
}
