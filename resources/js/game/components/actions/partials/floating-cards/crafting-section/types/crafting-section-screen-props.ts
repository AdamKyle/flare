import { CraftingTypes } from '../enums/crafting-types';

export default interface CraftingSectionScreenProps {
  setActiveCraftingType: (type: CraftingTypes) => void;
  locationRestrictionWarning?: string | null;
  clearLocationRestrictionWarning?: () => void;
}
