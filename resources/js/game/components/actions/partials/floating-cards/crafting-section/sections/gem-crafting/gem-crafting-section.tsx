import React, { ReactNode } from 'react';

import GemCraftingFlow from './components/gem-crafting-flow';
import CraftingDisciplineIntroduction from '../../shared/components/crafting-discipline-introduction';
import CraftingScreenTransition from '../../shared/crafting-screen-transition';
import { CraftingIntroductionStorageKey } from '../../shared/enums/crafting-introduction-storage-key';
import { useCraftingDisciplineIntroduction } from '../../shared/hooks/use-crafting-discipline-introduction';

const GEM_CRAFTING_INTRODUCTION_DESCRIPTION =
  'Gem Crafting lets you craft gems from Copper Coins, Shards, and Gold Dust across four tiers, then socket them into your weapons and armour for elemental resistance and bonus damage against enemies.';

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
    <CraftingScreenTransition
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
    </CraftingScreenTransition>
  );
};

export default GemCraftingSection;
