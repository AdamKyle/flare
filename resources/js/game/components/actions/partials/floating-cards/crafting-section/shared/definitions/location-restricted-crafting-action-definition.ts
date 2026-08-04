import { CraftingTypes } from '../../enums/crafting-types';

export default interface LocationRestrictedCraftingActionDefinition {
  crafting_type: CraftingTypes;
  is_available: boolean;
  warning_message: string;
}
