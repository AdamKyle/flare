import React, { ReactNode } from 'react';

import { useBatchCraftingActions } from '../api/hooks/use-batch-crafting-actions';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';

import { useGameData } from 'game-data/hooks/use-game-data';

import FormWizard from 'ui/form-wizard/form-wizard';
import Step from 'ui/form-wizard/step';

const INTRODUCTION_TOTAL_STEPS = 7;

const BatchCraftingIntroductionScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { acknowledging, acknowledgeInfo, error } =
    useBatchCraftingActions(characterId);

  const handleRequestNext = async (currentIndex: number): Promise<boolean> => {
    if (currentIndex !== INTRODUCTION_TOTAL_STEPS - 1) {
      return true;
    }

    const acknowledged = await acknowledgeInfo();

    if (!acknowledged) {
      return false;
    }

    navigation.resetTo(BatchCraftingScreenNames.TYPE, {});

    return false;
  };

  return (
    <FormWizard
      name="Batch Crafting"
      total_steps={INTRODUCTION_TOTAL_STEPS}
      is_loading={acknowledging}
      on_request_next={handleRequestNext}
      finish_label="I Understand"
      form_error={error ? { message: error } : null}
      embedded
      icon_navigation
    >
      <Step step_title="Craft">
        Craft a fixed amount of an eligible item, craft for experience when an
        eligible Crafting skill is not maxed, or build a crafted set.
      </Step>
      <Step step_title="Craft and Enchant">
        Craft and Enchant combines the existing Crafting and Enchanting systems.
        It can craft and enchant a fixed amount, work toward eligible
        experience, or build and enchant a crafted set.
      </Step>
      <Step step_title="Craft for Event">
        When an eligible crafting global event is active, Craft for Event uses
        the existing event crafting rules and works toward the active event
        goal. This option only appears when the backend says it is available.
      </Step>
      <Step step_title="Enchant for Event">
        When an eligible enchanting global event is active, Enchant for Event
        uses eligible event inventory items and existing Enchanting rules to
        work toward the active event goal. This option only appears when the
        backend says it is available.
      </Step>
      <Step step_title="Alchemy">
        Alchemy can craft a fixed amount of an eligible alchemy item or craft
        for experience while Alchemy is not maxed.
      </Step>
      <Step step_title="Trinketry">
        Trinketry automates eligible trinket crafting using the same Trinketry
        rules, costs, and progression as normal crafting.
      </Step>
      <Step step_title="Holy Oils">
        Holy Oils can apply selected oils to selected gear or to items from an
        inventory set, using the same Holy Oil rules and costs as the Work
        Bench.
      </Step>
    </FormWizard>
  );
};

export default BatchCraftingIntroductionScreen;
