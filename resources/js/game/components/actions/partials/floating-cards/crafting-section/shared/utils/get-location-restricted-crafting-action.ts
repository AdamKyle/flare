import { CraftingTypes } from '../../enums/crafting-types';
import LocationRestrictedCraftingActionDefinition from '../definitions/location-restricted-crafting-action-definition';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';

type LocationRestrictedCharacterAccess = Pick<
  CharacterSheetDefinition,
  | 'can_access_queen'
  | 'can_use_work_bench'
  | 'can_access_labyrinth_oracle'
  | 'can_access_seer_camp'
>;

const buildLocationRestrictedCraftingActions = (
  character: LocationRestrictedCharacterAccess | null
): LocationRestrictedCraftingActionDefinition[] => [
  {
    crafting_type: CraftingTypes.QUEEN_OF_HEARTS,
    is_available: character?.can_access_queen === true,
    warning_message:
      'You have moved away from Hell and can no longer access Queen of Hearts.',
  },
  {
    crafting_type: CraftingTypes.WORK_BENCH,
    is_available: character?.can_use_work_bench === true,
    warning_message:
      'You have moved away from Purgatory and can no longer access the Work Bench.',
  },
  {
    crafting_type: CraftingTypes.LABYRINTH_ORACLE,
    is_available: character?.can_access_labyrinth_oracle === true,
    warning_message:
      'You have moved away from the Labyrinth and can no longer access the Labyrinth Oracle.',
  },
  {
    crafting_type: CraftingTypes.SEER_CAMP,
    is_available: character?.can_access_seer_camp === true,
    warning_message:
      'You have moved away from Purgatory and can no longer access Seer Camp.',
  },
];

export const getLocationRestrictedCraftingAction = (
  activeCraftingType: CraftingTypes,
  character: LocationRestrictedCharacterAccess | null
): LocationRestrictedCraftingActionDefinition | null => {
  const restriction = buildLocationRestrictedCraftingActions(character).find(
    (candidate) => candidate.crafting_type === activeCraftingType
  );

  if (!restriction || restriction.is_available) {
    return null;
  }

  return restriction;
};
