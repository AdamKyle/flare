import clsx from 'clsx';
import React, { ReactNode } from 'react';

import BatchCraftingCountdown from './batch-crafting-countdown';
import BatchCraftingElapsedTime from './batch-crafting-elapsed-time';
import BatchCraftingNextAttemptCountdown from './batch-crafting-next-attempt-countdown';
import { useBatchCraftingActions } from '../api/hooks/use-batch-crafting-actions';
import { batchCraftingRunningSectionRegistry } from '../component-mapping/batch-crafting-running-section-registry';
import BatchCraftingScreenManager from '../component-mapping/batch-crafting-screen-manager';
import { BatchCraftingEndReason } from '../enums/batch-crafting-end-reason';
import { BatchCraftingScreenNames } from '../enums/batch-crafting-screen-names';
import { useBatchCraftingStatusContext } from '../hooks/use-batch-crafting-status-context';
import {
  craftModeLabel,
  dispositionLabel,
} from '../utils/batch-crafting-labels';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const BatchCraftingRunningPanel = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const navigation = BatchCraftingScreenManager.useScreenNavigation();

  const {
    status,
    loading,
    error: statusError,
  } = useBatchCraftingStatusContext();
  const {
    cancelling,
    dismissing,
    error: actionError,
    cancel,
    dismiss,
  } = useBatchCraftingActions(characterId);

  const batch = status?.batch;

  const handleCancel = async () => {
    await cancel();
  };

  const handleDismiss = async () => {
    const dismissed = await dismiss();

    if (!dismissed) {
      return;
    }

    navigation.resetTo(BatchCraftingScreenNames.TYPE, {});
  };

  const stateLabel = (): string => {
    if (status?.is_scheduled) {
      return 'Scheduled';
    }

    if (status?.is_waiting) {
      return 'Waiting';
    }

    if (status?.is_processing) {
      return 'Running';
    }

    if (batch?.ended_reason === BatchCraftingEndReason.CANCELLED) {
      return 'Cancelled';
    }

    return 'Completed';
  };

  const stateStyles = (): string => {
    if (status?.is_scheduled || status?.is_waiting) {
      return 'text-marigold-700 dark:text-marigold-300';
    }

    if (status?.is_processing) {
      return 'text-regent-st-blue-700 dark:text-regent-st-blue-300';
    }

    if (batch?.ended_reason === BatchCraftingEndReason.CANCELLED) {
      return 'text-rose-700 dark:text-rose-400';
    }

    return 'text-emerald-700 dark:text-emerald-400';
  };

  const renderTimer = () => {
    if (!batch) {
      return null;
    }

    if (status?.is_scheduled) {
      return (
        <BatchCraftingCountdown
          started_at={batch.started_at}
          scheduled_for={batch.scheduled_for}
        />
      );
    }

    return (
      <>
        <BatchCraftingElapsedTime
          processing_started_at={batch.processing_started_at}
          completed_at={batch.completed_at}
        />
        {status?.is_waiting && batch.next_attempt_at && (
          <BatchCraftingNextAttemptCountdown
            next_attempt_at={batch.next_attempt_at}
          />
        )}
      </>
    );
  };

  const renderActionError = () => {
    if (!actionError) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{actionError}</Alert>;
  };

  const renderStatusError = () => {
    if (!statusError) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{statusError}</Alert>;
  };

  const renderAction = () => {
    if (status?.can_cancel) {
      return (
        <Button
          label="Cancel"
          variant={ButtonVariant.DANGER}
          additional_css="w-full"
          disabled={cancelling}
          on_click={handleCancel}
        />
      );
    }

    if (status?.can_dismiss) {
      return (
        <Button
          label="Dismiss"
          variant={ButtonVariant.PRIMARY}
          additional_css="w-full"
          disabled={dismissing}
          on_click={handleDismiss}
        />
      );
    }

    return null;
  };

  const renderModeSection = () => {
    if (!batch) {
      return null;
    }

    const RunningSection =
      batchCraftingRunningSectionRegistry[batch.craft_mode];

    return <RunningSection batch={batch} character_id={characterId} />;
  };

  if (loading && !status) {
    return (
      <p role="status" aria-live="polite">
        Loading Batch Crafting status...
      </p>
    );
  }

  if (!batch) {
    if (statusError) {
      return <Alert variant={AlertVariant.DANGER}>{statusError}</Alert>;
    }

    return <p>No Batch Crafting run is currently visible.</p>;
  }

  return (
    <div className="space-y-3">
      {renderStatusError()}

      <div className="flex items-center justify-between gap-3">
        <div className="min-w-0 space-y-1">
          <h3 className="text-lg font-semibold">
            {craftModeLabel(batch.craft_mode)}
          </h3>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {dispositionLabel(batch.disposition)}
          </p>
        </div>
        <p
          role="status"
          aria-live="polite"
          className={clsx('shrink-0 text-sm font-semibold', stateStyles())}
        >
          {stateLabel()}
        </p>
      </div>

      <Separator />

      {renderTimer()}

      {renderModeSection()}

      {renderActionError()}

      <Separator />

      {renderAction()}
    </div>
  );
};

export default BatchCraftingRunningPanel;
