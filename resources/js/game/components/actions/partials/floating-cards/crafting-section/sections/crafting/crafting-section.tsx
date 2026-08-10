import React, { ReactNode } from 'react';

import CraftItemsFlow from './components/craft-items-flow';
import CraftingDisciplineIntroduction from '../../shared/components/crafting-discipline-introduction';
import CraftingScreenTransition from '../../shared/crafting-screen-transition';
import { CraftingIntroductionStorageKey } from '../../shared/enums/crafting-introduction-storage-key';
import { useCraftingDisciplineIntroduction } from '../../shared/hooks/use-crafting-discipline-introduction';
import CraftingSectionScreenProps from '../../types/crafting-section-screen-props';

const CRAFTING_INTRODUCTION_DESCRIPTION =
  'Crafting lets you make your own weapons and armour instead of relying only on what the shop sells. The shop is capped at how much it can offer, so crafting is a core part of gearing up your character beyond that cap as you level your crafting skill.';

const CraftingSection = ({
  setActiveCraftingType,
}: CraftingSectionScreenProps) => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingDisciplineIntroduction(CraftingIntroductionStorageKey.CRAFTING);

  const renderContent = (): ReactNode => {
    if (!introductionAcknowledged) {
      return (
        <CraftingDisciplineIntroduction
          title="Crafting"
          description={CRAFTING_INTRODUCTION_DESCRIPTION}
          on_acknowledge={acknowledgeIntroduction}
        />
      );
    }

    return <CraftItemsFlow setActiveCraftingType={setActiveCraftingType} />;
  };

  return (
    <CraftingScreenTransition
      screenKey={introductionAcknowledged ? 'craft-items' : 'introduction'}
      label={introductionAcknowledged ? 'Craft items' : 'Crafting introduction'}
    >
      {renderContent()}
    </CraftingScreenTransition>
  );
};

export default CraftingSection;
