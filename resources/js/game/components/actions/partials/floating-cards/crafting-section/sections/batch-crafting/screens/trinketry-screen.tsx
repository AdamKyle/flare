import React, { ReactNode, useState } from 'react';

import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { dispositionLabel } from '../utils/batch-crafting-labels';
import { buildTrinketryRequest } from '../utils/build-trinketry-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const DISPOSITION_OPTIONS: DropdownItem[] = [
  BatchCraftingDisposition.KEEP,
  BatchCraftingDisposition.DESTROY,
  BatchCraftingDisposition.KEEP_BEST_DESTROY_REST,
].map((disposition) => ({
  label: dispositionLabel(disposition),
  value: disposition,
}));

const TrinketryScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();

  const [disposition, setDisposition] = useState<DropdownItem | null>(null);

  const {
    starting,
    error: startError,
    start,
  } = useStartBatchCrafting(characterId);

  const trinketrySkill = status?.capabilities?.trinketry_skill ?? null;

  const selectedDisposition =
    typeof disposition?.value === 'string'
      ? (disposition.value as BatchCraftingDisposition)
      : null;

  const request = buildTrinketryRequest(selectedDisposition);

  const handleStart = async () => {
    if (!request) {
      return;
    }

    const started = await start(request);

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Trinketry</h3>

      {trinketrySkill && (
        <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Level</dt>
          <dd>{trinketrySkill.level}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Experience</dt>
          <dd>
            {trinketrySkill.current_xp.toLocaleString()} /{' '}
            {trinketrySkill.next_level_xp.toLocaleString()}
          </dd>
        </dl>
      )}

      <fieldset>
        <legend
          id="trinketry-disposition-legend"
          className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          Disposition
        </legend>
        <Dropdown
          aria_labelled_by="trinketry-disposition-legend"
          items={DISPOSITION_OPTIONS}
          on_select={setDisposition}
          pre_selected_item={disposition ?? undefined}
          selection_placeholder="Select a disposition"
        />
      </fieldset>

      {startError && <Alert variant={AlertVariant.DANGER}>{startError}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={!request || starting}
        on_click={handleStart}
      />

      <Button
        label="Back"
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full"
        on_click={() => navigation.pop()}
      />
    </div>
  );
};

export default TrinketryScreen;
