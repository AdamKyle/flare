import React, { ReactNode } from 'react';

import { useStartBatchCrafting } from '../api/hooks/use-start-batch-crafting';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import { buildEnchantEventRequest } from '../utils/build-enchant-event-request';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const EnchantEventScreen = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();
  const { status } = useBatchCraftingStatusContext();
  const { starting, error, start } = useStartBatchCrafting(characterId);

  const canEnchantForEvent =
    status?.capabilities?.can_enchant_for_event ?? false;
  const goal = status?.capabilities?.enchant_event_goal ?? null;

  const handleStart = async () => {
    const started = await start(buildEnchantEventRequest());

    if (started) {
      navigation.resetTo(BatchCraftingScreenNames.RUNNING, {});
    }
  };

  if (status !== null && (!canEnchantForEvent || goal === null)) {
    return (
      <div className="space-y-4">
        <h3 className="text-lg font-semibold">Enchant For Event</h3>
        <Alert variant={AlertVariant.WARNING}>
          There is no currently eligible Enchant Event to contribute to.
        </Alert>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">Enchant For Event</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400">
        Automatically enchants Event-provided items and contributes them to the
        current Enchant Event goal. When no Event items remain, real fallback
        items are crafted and enchanted to keep contributing.
      </p>

      {goal && (
        <div className="space-y-3">
          <ProgressBar
            value={goal.total_enchants}
            max={goal.max_enchants ?? goal.total_enchants}
            label="Event Goal Progress"
            value_label={`${goal.total_enchants} / ${goal.max_enchants ?? '?'}`}
            variant={ProgressBarVariant.PRIMARY}
          />
          <ProgressBar
            value={goal.character_contribution}
            max={goal.next_reward_at}
            label="Your Contribution"
            value_label={`${goal.character_contribution} / ${goal.next_reward_at}`}
            variant={ProgressBarVariant.PRIMARY}
          />
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Remaining Enchants: {goal.remaining_enchants.toLocaleString()}
          </p>
        </div>
      )}

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <Button
        label="Start Batch Crafting"
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
        disabled={starting || !canEnchantForEvent}
        on_click={handleStart}
      />
    </div>
  );
};

export default EnchantEventScreen;
