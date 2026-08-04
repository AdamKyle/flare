import { CraftingTypes } from '../enums/crafting-types';
import CraftingSectionScreenProps from '../types/crafting-section-screen-props';

export type PropsMapping = {
  [K in CraftingTypes]: CraftingSectionScreenProps;
};
