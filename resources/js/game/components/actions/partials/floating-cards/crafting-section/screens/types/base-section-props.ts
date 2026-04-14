import { CraftingTypes } from '../../enums/crafting-types';

export default interface BaseSectionProps {
  setActiveCraftingType: (type: CraftingTypes) => void;
}
