import React from 'react';

import CraftItemsFlow from './components/craft-items-flow';
import CraftingIntroduction from './components/crafting-introduction';
import { useCraftingIntroduction } from './hooks/use-crafting-introduction';
import CraftingScreenTransition from '../../shared/crafting-screen-transition';
import CraftingSectionScreenProps from '../../types/crafting-section-screen-props';

const CraftingSection = ({
  setActiveCraftingType,
}: CraftingSectionScreenProps) => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingIntroduction();

  return (
    <CraftingScreenTransition
      screenKey={introductionAcknowledged ? 'craft-items' : 'introduction'}
      label={introductionAcknowledged ? 'Craft items' : 'Crafting introduction'}
    >
      {introductionAcknowledged ? (
        <CraftItemsFlow setActiveCraftingType={setActiveCraftingType} />
      ) : (
        <CraftingIntroduction onAcknowledge={acknowledgeIntroduction} />
      )}
    </CraftingScreenTransition>
  );
};

export default CraftingSection;
