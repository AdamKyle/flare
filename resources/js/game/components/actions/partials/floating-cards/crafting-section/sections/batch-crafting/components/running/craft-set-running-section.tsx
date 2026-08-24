import React, { ReactNode } from 'react';

import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';
import { BatchCraftingOutputDestination } from '../../enums/batch-crafting-output-destination';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { useOpenBatchCraftedItem } from '../../hooks/use-open-batch-crafted-item';
import { useOpenBatchCraftingSet } from '../../hooks/use-open-batch-crafting-set';
import {
  craftSetPositionLabel,
  endReasonLabel,
} from '../../utils/batch-crafting-labels';
import { getBatchCraftedItemLinkLabel } from '../../utils/get-batch-crafted-item-link-label';
import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CraftSetRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const { openBatchCraftedItem } = useOpenBatchCraftedItem({ character_id });
  const { openBatchCraftingSet } = useOpenBatchCraftingSet({ character_id });
  const setProgress = batch.set_progress;
  const isRunning = batch.status === BatchCraftingStatus.RUNNING;

  const isNormalSetDestination =
    (batch.output_destination ===
      BatchCraftingOutputDestination.INVENTORY_SET ||
      batch.output_destination ===
        BatchCraftingOutputDestination.CRAFTED_ITEMS_SET) &&
    batch.destination_set_id !== null &&
    batch.destination_set_name !== null;

  const handleOpenDestination = () => {
    if (
      !isNormalSetDestination ||
      batch.destination_set_id === null ||
      batch.destination_set_name === null
    ) {
      return;
    }

    openBatchCraftingSet(batch.destination_set_id, batch.destination_set_name);
  };

  const renderDestination = () => {
    if (batch.output_destination === BatchCraftingOutputDestination.INVENTORY) {
      return <p className="text-sm">Inventory</p>;
    }

    if (isNormalSetDestination && batch.destination_set_name) {
      return (
        <LinkButton
          label={batch.destination_set_name}
          variant={ButtonVariant.PRIMARY}
          on_click={handleOpenDestination}
          aria_label={`Open ${batch.destination_set_name}`}
        />
      );
    }

    return null;
  };

  const renderCurrentItem = () => {
    if (!batch.current_item_name) {
      return <>—</>;
    }

    const craftedItemLinkLabel = getBatchCraftedItemLinkLabel(batch);

    if (craftedItemLinkLabel !== null) {
      return (
        <LinkButton
          label={batch.current_item_name}
          variant={ButtonVariant.PRIMARY}
          on_click={() => openBatchCraftedItem(batch)}
          aria_label={craftedItemLinkLabel}
        />
      );
    }

    return <>{batch.current_item_name}</>;
  };

  return (
    <div className="space-y-3">
      <BatchCraftingDetailSection title="Progress">
        {setProgress && (
          <ProgressBar
            value={setProgress.completed_entries}
            max={setProgress.total_entries}
            label="Set Entries"
            value_label={`${setProgress.completed_entries} / ${setProgress.total_entries}`}
            variant={ProgressBarVariant.PRIMARY}
          />
        )}

        {batch.destination_capacity && (
          <ProgressBar
            value={batch.destination_capacity.current}
            max={batch.destination_capacity.max}
            label="Destination Capacity"
            value_label={`${batch.destination_capacity.current} / ${batch.destination_capacity.max}`}
            variant={ProgressBarVariant.ARTIC}
          />
        )}
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Results">
        <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Destination</dt>
          <dd>{renderDestination()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Current Position</dt>
          <dd>
            {craftSetPositionLabel(setProgress?.current_position ?? null) ??
              '—'}
          </dd>
          <dt className="text-gray-600 dark:text-gray-400">Current Item</dt>
          <dd>{renderCurrentItem()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Completed</dt>
          <dd>{setProgress?.completed_entries ?? 0}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Remaining</dt>
          <dd>{setProgress?.remaining_entries ?? 0}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Failed</dt>
          <dd>{batch.failed_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Spent</dt>
          <dd>{batch.gold_spent.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Gained</dt>
          <dd>{batch.gold_gained.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Left</dt>
          <dd>{batch.gold_left.toLocaleString()}</dd>
        </dl>
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Activity">
        <BatchCraftingOutcomeChart
          chart_points={batch.chart_points}
          processing_started_at={batch.processing_started_at}
        />
      </BatchCraftingDetailSection>

      {!isRunning && batch.ended_reason && setProgress && (
        <BatchCraftingDetailSection title="Completion">
          <Alert variant={AlertVariant.INFO}>
            {endReasonLabel(batch.ended_reason)}. Completed{' '}
            {setProgress.completed_entries.toLocaleString()} of{' '}
            {setProgress.total_entries.toLocaleString()} set entries, with{' '}
            {setProgress.remaining_entries.toLocaleString()} remaining and{' '}
            {batch.failed_count.toLocaleString()} failed.
          </Alert>
        </BatchCraftingDetailSection>
      )}
    </div>
  );
};

export default CraftSetRunningSection;
