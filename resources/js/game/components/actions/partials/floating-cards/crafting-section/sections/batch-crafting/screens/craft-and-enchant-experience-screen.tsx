import React, { ReactNode, useState } from 'react';

import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import CraftingSkillProgressList from '../components/crafting-skill-progress-list';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { buildCraftAndEnchantExperienceRequest } from '../utils/build-craft-and-enchant-experience-request';
import { createEnumValueGuard } from '../utils/create-enum-value-guard';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const DISPOSITION_OPTIONS: DropdownItem[] = [
  { label: 'Keep', value: BatchCraftingDisposition.KEEP },
  { label: 'Sell', value: BatchCraftingDisposition.SELL },
  { label: 'Destroy', value: BatchCraftingDisposition.DESTROY },
  { label: 'List', value: BatchCraftingDisposition.LIST },
  { label: 'Disenchant', value: BatchCraftingDisposition.DISENCHANT },
  {
    label: 'Keep Best and Sell Rest',
    value: BatchCraftingDisposition.KEEP_BEST_SELL_REST,
  },
  {
    label: 'Keep Best and Destroy Rest',
    value: BatchCraftingDisposition.KEEP_BEST_DESTROY_REST,
  },
  {
    label: 'Keep Best and Disenchant Rest',
    value: BatchCraftingDisposition.KEEP_BEST_DISENCHANT_REST,
  },
];

const isBatchCraftingDisposition = createEnumValueGuard(
  BatchCraftingDisposition
);

const CraftAndEnchantExperienceScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();
  const { starting, error, start } = useStartBatchCrafting(characterId);

  const [disposition, setDisposition] = useState<BatchCraftingDisposition>(
    BatchCraftingDisposition.KEEP
  );
  const [listingPriceText, setListingPriceText] = useState('');

  const canCraftAndEnchantForExperience =
    status?.capabilities?.can_craft_and_enchant_for_experience ?? false;
  const craftingSkills = status?.capabilities?.crafting_skills ?? [];
  const enchantingSkill = status?.capabilities?.enchanting_skill ?? null;
  const showListingPriceField = disposition === BatchCraftingDisposition.LIST;

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

  const handleListingPriceChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    setListingPriceText(event.target.value);
  };

  const listingPrice = Number(listingPriceText);
  const hasValidListingPrice =
    Number.isFinite(listingPrice) &&
    Number.isInteger(listingPrice) &&
    listingPrice >= 1;

  const canStart =
    canCraftAndEnchantForExperience &&
    !starting &&
    (!showListingPriceField || hasValidListingPrice);

  const handleStart = async () => {
    if (!canStart) {
      return;
    }

    const started = await start(
      buildCraftAndEnchantExperienceRequest(
        disposition,
        showListingPriceField ? listingPrice : null
      )
    );

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  if (status !== null && !canCraftAndEnchantForExperience) {
    return (
      <div className="space-y-4">
        <h3 className="text-lg font-semibold">
          Craft and Enchant For Experience
        </h3>
        <Alert variant={AlertVariant.WARNING}>
          There is no meaningful Crafting or Enchanting progression currently
          available for this workflow.
        </Alert>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">
        Craft and Enchant For Experience
      </h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Automatically crafts and enchants meaningful items to progress your
        available Crafting disciplines and your Enchanting skill.
      </p>

      <CraftingSkillProgressList skills={craftingSkills} />

      {enchantingSkill && (
        <ProgressBar
          value={enchantingSkill.current_xp}
          max={enchantingSkill.next_level_xp}
          label={`Enchanting (Level ${enchantingSkill.level})`}
          value_label={`${enchantingSkill.current_xp.toLocaleString()} / ${enchantingSkill.next_level_xp.toLocaleString()}`}
          variant={ProgressBarVariant.PRIMARY}
        />
      )}

      <fieldset>
        <legend
          id="craft-and-enchant-experience-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="craft-and-enchant-experience-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={handleDispositionSelect}
          pre_selected_item={selectedDispositionItem}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {showListingPriceField && (
        <div>
          <label
            htmlFor="craft-and-enchant-experience-listing-price"
            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Listing Price
          </label>
          <input
            id="craft-and-enchant-experience-listing-price"
            type="number"
            min={1}
            step={1}
            value={listingPriceText}
            onChange={handleListingPriceChange}
            className="w-full rounded-md border border-gray-500 bg-white p-2 text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
      )}

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!canStart}
        on_click={handleStart}
      />
    </div>
  );
};

export default CraftAndEnchantExperienceScreen;
