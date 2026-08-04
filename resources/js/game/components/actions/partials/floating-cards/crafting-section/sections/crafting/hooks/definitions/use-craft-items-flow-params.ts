import { CraftingTypes } from '../../../../enums/crafting-types';

export default interface UseCraftItemsFlowParams {
  setActiveCraftingType: (type: CraftingTypes) => void;
}
