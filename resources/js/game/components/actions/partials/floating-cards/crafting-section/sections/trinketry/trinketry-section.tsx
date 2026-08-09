import React, { ReactNode } from 'react';

import TrinketryFlow from './components/trinketry-flow';
import CraftingDisciplineIntroduction from '../../shared/components/crafting-discipline-introduction';
import CraftingScreenTransition from '../../shared/crafting-screen-transition';
import { CraftingIntroductionStorageKey } from '../../shared/enums/crafting-introduction-storage-key';
import { useCraftingDisciplineIntroduction } from '../../shared/hooks/use-crafting-discipline-introduction';

const TRINKETRY_INTRODUCTION_DESCRIPTION =
  'Trinketry unlocks once you reach Purgatory and lets you craft trinkets using currencies such as Copper Coins and Gold Dust. You can only wear one trinket at a time, and they grant Ambush and Counter stats that help you land or resist extra attacks in combat.';

const TrinketrySection = (): ReactNode => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingDisciplineIntroduction(
      CraftingIntroductionStorageKey.TRINKETRY
    );

  const renderContent = (): ReactNode => {
    if (!introductionAcknowledged) {
      return (
        <CraftingDisciplineIntroduction
          title="Trinket Crafting"
          description={TRINKETRY_INTRODUCTION_DESCRIPTION}
          on_acknowledge={acknowledgeIntroduction}
        />
      );
    }

    return <TrinketryFlow />;
  };

  return (
    <CraftingScreenTransition
      screenKey={introductionAcknowledged ? 'trinketry-items' : 'introduction'}
      label={
        introductionAcknowledged ? 'Trinketry items' : 'Trinketry introduction'
      }
    >
      {renderContent()}
    </CraftingScreenTransition>
  );
};

export default TrinketrySection;
