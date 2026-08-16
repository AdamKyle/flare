import React, { ReactNode, useCallback, useRef, useState } from 'react';

import { ScreenMapper } from './component-mapping/screen-registry';
import { CraftingTypes } from './enums/crafting-types';
import { useManageCraftingCardVisibility } from './hooks/use-manage-crafting-card-visibility';
import CraftingScreenTransition from './shared/crafting-screen-transition';
import { useLocationRestrictedCraftingAction } from './shared/hooks/use-location-restricted-crafting-action';
import FloatingCard from '../../../components/icon-section/floating-card';

import { useGameData } from 'game-data/hooks/use-game-data';

const CraftingCard = (): ReactNode => {
  const { gameData } = useGameData();
  const { closeCraftingCard } = useManageCraftingCardVisibility();

  const [activeCraftingType, setActiveCraftingType] = useState<CraftingTypes>(
    CraftingTypes.HOME
  );

  const { locationRestrictionWarning, clearLocationRestrictionWarning } =
    useLocationRestrictedCraftingAction({
      activeCraftingType,
      setActiveCraftingType,
      character: gameData?.character ?? null,
    });

  const backHandlerRef = useRef<(() => void) | null>(null);
  const registerBackHandler = useCallback((handler: (() => void) | null) => {
    backHandlerRef.current = handler;
  }, []);

  const ActiveScreen = ScreenMapper[activeCraftingType];

  const handleCloseCraftingCard = () => {
    clearLocationRestrictionWarning();
    closeCraftingCard();
  };

  const handleBackAction = () => {
    if (activeCraftingType === CraftingTypes.HOME) {
      return;
    }

    if (backHandlerRef.current) {
      return backHandlerRef.current();
    }

    return setActiveCraftingType(CraftingTypes.HOME);
  };

  return (
    <FloatingCard
      title={activeCraftingType}
      close_action={handleCloseCraftingCard}
      back_action={
        activeCraftingType === CraftingTypes.HOME ? undefined : handleBackAction
      }
    >
      <CraftingScreenTransition
        screenKey={activeCraftingType}
        label={`${activeCraftingType} crafting screen`}
      >
        <ActiveScreen
          setActiveCraftingType={setActiveCraftingType}
          locationRestrictionWarning={locationRestrictionWarning}
          clearLocationRestrictionWarning={clearLocationRestrictionWarning}
          registerBackHandler={registerBackHandler}
        />
      </CraftingScreenTransition>
    </FloatingCard>
  );
};

export default CraftingCard;
