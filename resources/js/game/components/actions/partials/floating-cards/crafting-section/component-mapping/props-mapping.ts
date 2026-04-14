import { CraftingTypes } from '../enums/crafting-types';

export type PropsMapping = {
  [CraftingTypes.HOME]: {
    setActiveCraftingType: (type: CraftingTypes) => void;
  };
  [CraftingTypes.CRAFT]: {
    setActiveCraftingType: (type: CraftingTypes) => void;
  };
  [CraftingTypes.ENCHANT]: {
    setActiveCraftingType: (type: CraftingTypes) => void;
  };
  [CraftingTypes.ALCHEMY]: {
    setActiveCraftingType: (type: CraftingTypes) => void;
  };
  [CraftingTypes.TRINKETS]: {
    setActiveCraftingType: (type: CraftingTypes) => void;
  };
};
