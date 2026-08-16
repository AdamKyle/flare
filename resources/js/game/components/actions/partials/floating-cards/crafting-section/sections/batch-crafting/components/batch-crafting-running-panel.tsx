import React, { ReactNode } from 'react';

import { useBatchCraftingActions } from '../api/hooks/use-batch-crafting-actions';
import { useBatchCraftingStatus } from '../api/hooks/use-batch-crafting-status';
import { BatchCraftingEndReason } from '../enums/batch-crafting-end-reason';
import {
  dispositionLabel,
  endReasonLabel,
  outputDestinationLabel,
} from '../utils/batch-crafting-labels';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const BatchCraftingRunningPanel = (): ReactNode => {
  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? 0;
  const userId = gameData?.character?.user_id ?? 0;

  const {
    status,
    loading,
    error: statusError,
    refetch,
  } = useBatchCraftingStatus({
    characterId,
    userId,
  });
  const {
    cancelling,
    dismissing,
    error: actionError,
    cancel,
    dismiss,
  } = useBatchCraftingActions(characterId);

  const batch = status?.batch;

  const handleCancel = async () => {
    const cancelled = await cancel();

    if (cancelled) {
      await refetch();
    }
  };

  const handleDismiss = async () => {
    const dismissed = await dismiss();

    if (dismissed) {
      await refetch();
    }
  };

  const stateLabel = (): string => {
    if (status?.is_running) {
      return 'Running';
    }

    if (batch?.ended_reason === BatchCraftingEndReason.CANCELLED) {
      return 'Cancelled';
    }

    return 'Completed';
  };

  const renderEndReason = () => {
    if (!batch?.ended_reason || status?.is_running) {
      return null;
    }

    return <p>Reason: {endReasonLabel(batch.ended_reason)}</p>;
  };

  const renderOutputDestination = () => {
    const label = outputDestinationLabel(batch?.output_destination ?? null);

    if (!label) {
      return null;
    }

    return <p>Destination: {label}</p>;
  };

  const renderCurrentItem = () => {
    if (!batch?.current_item_name) {
      return null;
    }

    return <p>Current item: {batch.current_item_name}</p>;
  };

  const renderFinishedNotice = () => {
    if (status?.is_running) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        This Batch Crafting run has finished. Dismiss it when you are ready.
      </Alert>
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

  const renderCancelButton = () => {
    if (!status?.can_cancel) {
      return null;
    }

    return (
      <Button
        label="Cancel"
        variant={ButtonVariant.DANGER}
        additional_css="flex-1"
        disabled={cancelling}
        on_click={handleCancel}
      />
    );
  };

  const renderDismissButton = () => {
    if (!status?.can_dismiss) {
      return null;
    }

    return (
      <Button
        label="Dismiss"
        variant={ButtonVariant.PRIMARY}
        additional_css="flex-1"
        disabled={dismissing}
        on_click={handleDismiss}
      />
    );
  };

  if (loading && !status) {
    return <p>Loading Batch Crafting status...</p>;
  }

  if (!batch) {
    if (statusError) {
      return <Alert variant={AlertVariant.DANGER}>{statusError}</Alert>;
    }

    return <p>No Batch Crafting run is currently visible.</p>;
  }

  return (
    <div className="space-y-3">
      <h3 className="text-lg font-semibold">Batch Crafting</h3>

      {renderStatusError()}

      <div className="space-y-1">
        <p>Craft &middot; Craft Amount</p>
        <p aria-live="polite">
          Status: <span className="font-semibold">{stateLabel()}</span>.{' '}
          {batch.completed_amount} of {batch.requested_amount ?? 0} crafted.
        </p>
        {renderEndReason()}
        {renderCurrentItem()}
        <p>Disposition: {dispositionLabel(batch.disposition)}</p>
        {renderOutputDestination()}
        <p>
          Progress: {batch.completed_amount} of {batch.requested_amount ?? 0} (
          {batch.remaining_amount ?? 0} remaining)
        </p>
        <p>Gold: {batch.gold_left}</p>
      </div>

      {renderFinishedNotice()}
      {renderActionError()}

      <div className="flex gap-2">
        {renderCancelButton()}
        {renderDismissButton()}
      </div>
    </div>
  );
};

export default BatchCraftingRunningPanel;
