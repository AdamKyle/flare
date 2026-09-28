import React, { ReactNode } from 'react';

import GemCraftingFlow from './components/gem-crafting-flow';
import ScreenTransition from '../../../../../../../reusable-components/screen-transition/screen-transition';
import CraftingDisciplineIntroduction from '../../shared/components/crafting-discipline-introduction';
import { CraftingIntroductionStorageKey } from '../../shared/enums/crafting-introduction-storage-key';
import { useCraftingDisciplineIntroduction } from '../../shared/hooks/use-crafting-discipline-introduction';

const GEM_CRAFTING_INTRODUCTION_DESCRIPTION =
  'Gem Crafting creates role-specialized Gems: Tier 1 grants a Gem Ability and two raw stats; Tier 2 grants raw stats and direct combat modifiers; Tier 3 specializes Class Rank, Class Mastery, Weapon Mastery, and class-skill progression; Tier 4 specializes atonement, penetration, Character XP, and currency gains.';

const GemCraftingSection = (): ReactNode => {
  const { introductionAcknowledged, acknowledgeIntroduction } =
    useCraftingDisciplineIntroduction(
      CraftingIntroductionStorageKey.GEM_CRAFTING
    );

  const renderContent = (): ReactNode => {
    if (!introductionAcknowledged) {
      return (
        <CraftingDisciplineIntroduction
          title="Gem Crafting"
          description={GEM_CRAFTING_INTRODUCTION_DESCRIPTION}
          on_acknowledge={acknowledgeIntroduction}
        />
      );
    }

    return <GemCraftingFlow />;
  };

  return (
    <ScreenTransition
      screenKey={
        introductionAcknowledged ? 'gem-crafting-items' : 'introduction'
      }
      label={
        introductionAcknowledged
          ? 'Gem crafting items'
          : 'Gem crafting introduction'
      }
    >
      {renderContent()}
    </ScreenTransition>
  );
};

export default GemCraftingSection;
