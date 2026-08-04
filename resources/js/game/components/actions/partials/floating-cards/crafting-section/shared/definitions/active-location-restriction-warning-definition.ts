import { CraftingTypes } from '../../enums/crafting-types';

export default interface ActiveLocationRestrictionWarningDefinition {
  crafting_type: CraftingTypes;
  message: string;
}
