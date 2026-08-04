import { useEffect, useState } from 'react';

import ActiveLocationRestrictionWarningDefinition from '../definitions/active-location-restriction-warning-definition';
import UseLocationRestrictedCraftingActionDefinition from './definitions/use-location-restricted-crafting-action-definition';
import UseLocationRestrictedCraftingActionParams from './definitions/use-location-restricted-crafting-action-params';
import { CraftingTypes } from '../../enums/crafting-types';
import { getLocationRestrictedCraftingAction } from '../utils/get-location-restricted-crafting-action';

export const useLocationRestrictedCraftingAction = ({
  activeCraftingType,
  setActiveCraftingType,
  character,
}: UseLocationRestrictedCraftingActionParams): UseLocationRestrictedCraftingActionDefinition => {
  const [activeWarning, setActiveWarning] =
    useState<ActiveLocationRestrictionWarningDefinition | null>(null);

  const canAccessQueen = character?.can_access_queen === true;
  const canUseWorkBench = character?.can_use_work_bench === true;
  const canAccessLabyrinthOracle =
    character?.can_access_labyrinth_oracle === true;
  const canAccessSeerCamp = character?.can_access_seer_camp === true;

  useEffect(() => {
    const characterAccess = {
      can_access_queen: canAccessQueen,
      can_use_work_bench: canUseWorkBench,
      can_access_labyrinth_oracle: canAccessLabyrinthOracle,
      can_access_seer_camp: canAccessSeerCamp,
    };

    const activeRestriction = getLocationRestrictedCraftingAction(
      activeCraftingType,
      characterAccess
    );

    if (activeRestriction) {
      setActiveWarning({
        crafting_type: activeRestriction.crafting_type,
        message: activeRestriction.warning_message,
      });
      setActiveCraftingType(CraftingTypes.HOME);

      return;
    }

    if (!activeWarning) {
      return;
    }

    const warningActionStillRestricted = getLocationRestrictedCraftingAction(
      activeWarning.crafting_type,
      characterAccess
    );

    if (warningActionStillRestricted) {
      return;
    }

    setActiveWarning(null);
  }, [
    activeCraftingType,
    activeWarning,
    canAccessQueen,
    canUseWorkBench,
    canAccessLabyrinthOracle,
    canAccessSeerCamp,
    setActiveCraftingType,
  ]);

  const clearLocationRestrictionWarning = () => {
    setActiveWarning(null);
  };

  return {
    locationRestrictionWarning: activeWarning?.message ?? null,
    clearLocationRestrictionWarning,
  };
};
