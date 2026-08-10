import React, { ReactNode } from 'react';

import AlchemyFlow from './components/alchemy-flow';
import CraftingDisciplineIntroduction from '../../shared/components/crafting-discipline-introduction';
import CraftingScreenTransition from '../../shared/crafting-screen-transition';
import { CraftingIntroductionStorageKey } from '../../shared/enums/crafting-introduction-storage-key';
import { useCraftingDisciplineIntroduction } from '../../shared/hooks/use-crafting-discipline-introduction';

const ALCHEMY_INTRODUCTION_DESCRIPTION =
  "Alchemy unlocks later in your progression after completing the one-off 'I Dream of Alchemy' quest on Surface, and uses Shards and Gold Dust to create potions that boost your stats, kingdom-destruction items, and Holy Oils for upgrading your gear.";

const AlchemySection = (): ReactNode => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingDisciplineIntroduction(CraftingIntroductionStorageKey.ALCHEMY);

  const renderContent = (): ReactNode => {
    if (!introductionAcknowledged) {
      return (
        <CraftingDisciplineIntroduction
          title="Alchemy"
          description={ALCHEMY_INTRODUCTION_DESCRIPTION}
          on_acknowledge={acknowledgeIntroduction}
        />
      );
    }

    return <AlchemyFlow />;
  };

  return (
    <CraftingScreenTransition
      screenKey={introductionAcknowledged ? 'alchemy-items' : 'introduction'}
      label={
        introductionAcknowledged ? 'Alchemy items' : 'Alchemy introduction'
      }
    >
      {renderContent()}
    </CraftingScreenTransition>
  );
};

export default AlchemySection;
