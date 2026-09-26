import React, { ReactNode } from 'react';

import EnchantingFlow from './components/enchanting-flow';
import ScreenTransition from '../../../../../../../reusable-components/screen-transition/screen-transition';
import CraftingDisciplineIntroduction from '../../shared/components/crafting-discipline-introduction';
import { CraftingIntroductionStorageKey } from '../../shared/enums/crafting-introduction-storage-key';
import { useCraftingDisciplineIntroduction } from '../../shared/hooks/use-crafting-discipline-introduction';

const ENCHANTING_INTRODUCTION_DESCRIPTION =
  'Enchanting adds prefix and suffix affixes to your gear and is one of the best ways to make your character stronger. Your Intelligence and Enchanting skill decide which enchantments you can use, and disenchanting an unwanted enchanted item returns Gold Dust.';

const EnchantingSection = (): ReactNode => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingDisciplineIntroduction(
      CraftingIntroductionStorageKey.ENCHANTING
    );

  const renderContent = (): ReactNode => {
    if (!introductionAcknowledged) {
      return (
        <CraftingDisciplineIntroduction
          title="Enchanting"
          description={ENCHANTING_INTRODUCTION_DESCRIPTION}
          on_acknowledge={acknowledgeIntroduction}
        />
      );
    }

    return <EnchantingFlow />;
  };

  return (
    <ScreenTransition
      screenKey={introductionAcknowledged ? 'enchant-items' : 'introduction'}
      label={
        introductionAcknowledged ? 'Enchant items' : 'Enchanting introduction'
      }
    >
      {renderContent()}
    </ScreenTransition>
  );
};

export default EnchantingSection;
