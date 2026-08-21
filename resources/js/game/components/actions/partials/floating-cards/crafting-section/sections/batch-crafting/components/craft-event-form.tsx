import React, { ReactNode } from 'react';

import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { buildCraftEventRequest } from '../utils/build-craft-event-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CraftEventForm = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();
  const { starting, error, start } = useStartBatchCrafting(characterId);

  const canCraftForEvent = status?.capabilities?.can_craft_for_event ?? false;
  const goal = status?.capabilities?.event_goal ?? null;

  const handleStart = async () => {
    const started = await start(buildCraftEventRequest());

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  if (status !== null && (!canCraftForEvent || goal === null)) {
    return (
      <div className="space-y-4">
        <h3 className="text-lg font-semibold">Craft For Event</h3>
        <Alert variant={AlertVariant.WARNING}>
          There is no currently eligible Craft Event to contribute to.
        </Alert>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Craft For Event</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Automatically crafts items and contributes them to the current Craft
        Event goal.
      </p>

      {goal && (
        <div className="space-y-3">
          <ProgressBar
            value={goal.total_crafts}
            max={goal.max_crafts ?? goal.total_crafts}
            label="Event Goal Progress"
            value_label={`${goal.total_crafts} / ${goal.max_crafts ?? '?'}`}
            variant={ProgressBarVariant.PRIMARY}
          />
          <ProgressBar
            value={goal.character_contribution}
            max={goal.next_reward_at}
            label="Your Contribution"
            value_label={`${goal.character_contribution} / ${goal.next_reward_at}`}
            variant={ProgressBarVariant.PRIMARY}
          />
        </div>
      )}

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <Button
        label="Batch Craft"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={starting || !canCraftForEvent}
        on_click={handleStart}
      />
    </div>
  );
};

export default CraftEventForm;
