import { CraftingTypes } from '../../../enums/crafting-types';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

export default interface UseLocationRestrictedCraftingActionParams {
  activeCraftingType: CraftingTypes;
  setActiveCraftingType: (type: CraftingTypes) => void;
  character: CharacterSheetDefinition | null;
}
