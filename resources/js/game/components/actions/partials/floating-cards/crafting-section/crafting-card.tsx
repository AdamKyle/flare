import React, { ReactNode, useState } from 'react';

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

  const ActiveScreen = ScreenMapper[activeCraftingType];

  const handleCloseCraftingCard = () => {
    clearLocationRestrictionWarning();
    closeCraftingCard();
  };

  const renderBackAction = () => {
    if (activeCraftingType === CraftingTypes.HOME) {
      return;
    }

    return setActiveCraftingType(CraftingTypes.HOME);
  };

  return (
    <FloatingCard
      title={activeCraftingType}
      close_action={handleCloseCraftingCard}
      back_action={
        activeCraftingType === CraftingTypes.HOME ? undefined : renderBackAction
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
        />
      </CraftingScreenTransition>
    </FloatingCard>
  );
};

export default CraftingCard;
