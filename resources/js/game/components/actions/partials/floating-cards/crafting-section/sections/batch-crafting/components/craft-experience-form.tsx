import React, { ReactNode, useState } from 'react';

import CraftingSkillProgressList from './crafting-skill-progress-list';
import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { buildCraftExperienceRequest } from '../utils/build-craft-experience-request';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const DISPOSITION_OPTIONS: DropdownItem[] = [
  { label: 'Keep', value: BatchCraftingDisposition.KEEP },
  { label: 'Sell', value: BatchCraftingDisposition.SELL },
  { label: 'Destroy', value: BatchCraftingDisposition.DESTROY },
  {
    label: 'Keep Best and Sell Rest',
    value: BatchCraftingDisposition.KEEP_BEST_SELL_REST,
  },
  {
    label: 'Keep Best and Destroy Rest',
    value: BatchCraftingDisposition.KEEP_BEST_DESTROY_REST,
  },
];

const isBatchCraftingDisposition = createEnumValueGuard(
  BatchCraftingDisposition
);

const CraftExperienceForm = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();
  const { starting, error, start } = useStartBatchCrafting(characterId);

  const [disposition, setDisposition] = useState<BatchCraftingDisposition>(
    BatchCraftingDisposition.KEEP
  );

  const canCraftForExperience =
    status?.capabilities?.can_craft_for_experience ?? false;
  const craftingSkills = status?.capabilities?.crafting_skills ?? [];

  const selectedDispositionItem =
    DISPOSITION_OPTIONS.find((option) => option.value === disposition) ??
    DISPOSITION_OPTIONS[0];

  const handleDispositionSelect = (item: DropdownItem) => {
    if (
      typeof item.value !== 'string' ||
      !isBatchCraftingDisposition(item.value)
    ) {
      return;
    }

    setDisposition(item.value);
  };

  const handleStart = async () => {
    const started = await start(buildCraftExperienceRequest(disposition));

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  if (status !== null && !canCraftForExperience) {
    return (
      <div className="space-y-4">
        <h3 className="text-lg font-semibold">Craft For Experience</h3>
        <Alert variant={AlertVariant.WARNING}>
          There is nothing currently available to craft for experience. Every
          Crafting skill is either maxed or has no meaningful item to craft
          right now.
        </Alert>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Craft For Experience</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Automatically crafts items capable of progressing your available
        Crafting skills: Weapon, Armour, Ring, and Spell Crafting.
      </p>

      <CraftingSkillProgressList skills={craftingSkills} />

      <fieldset>
        <legend
          id="craft-experience-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="craft-experience-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={handleDispositionSelect}
          pre_selected_item={selectedDispositionItem}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={starting || !canCraftForExperience}
        on_click={handleStart}
      />
    </div>
  );
};

export default CraftExperienceForm;
