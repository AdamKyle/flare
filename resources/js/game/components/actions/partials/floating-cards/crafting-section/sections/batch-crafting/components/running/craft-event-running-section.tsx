import React, { ReactNode } from 'react';

import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { useOpenBatchCraftedItem } from '../../hooks/use-open-batch-crafted-item';
import {
  craftingDisciplineLabel,
  endReasonLabel,
} from '../../utils/batch-crafting-labels';
import { getBatchCraftedItemLinkLabel } from '../../utils/get-batch-crafted-item-link-label';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const CraftEventRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const event = batch.event_progress;
  const isRunning = batch.status === BatchCraftingStatus.RUNNING;
  const { openBatchCraftedItem } = useOpenBatchCraftedItem({ character_id });
  const disciplineLabel = craftingDisciplineLabel(
    event?.current_crafting_type ?? null
  );

  const handleOpenCraftedItem = () => {
    openBatchCraftedItem(batch);
  };

  const renderCurrentItem = () => {
    if (!batch.current_item_name) {
      return null;
    }

    const craftedItemLinkLabel = getBatchCraftedItemLinkLabel(batch);

    if (craftedItemLinkLabel !== null) {
      return (
        <LinkButton
          label={batch.current_item_name}
          variant={ButtonVariant.PRIMARY}
          on_click={handleOpenCraftedItem}
          aria_label={craftedItemLinkLabel}
          additional_css="font-semibold"
        />
      );
    }

    return <span className="font-semibold">{batch.current_item_name}</span>;
  };

  return (
    <div className="space-y-3">
      <BatchCraftingDetailSection title="Progress">
        {event && event.max_crafts !== null && (
          <ProgressBar
            value={event.total_crafts ?? 0}
            max={event.max_crafts}
            label="Event Goal Progress"
            value_label={`${event.total_crafts ?? 0} / ${event.max_crafts}`}
            variant={ProgressBarVariant.PRIMARY}
          />
        )}

        {event &&
          event.character_contribution !== null &&
          event.next_reward_at !== null && (
            <ProgressBar
              value={event.character_contribution}
              max={event.next_reward_at}
              label="Your Contribution"
              value_label={`${event.character_contribution} / ${event.next_reward_at}`}
              variant={ProgressBarVariant.PRIMARY}
            />
          )}

        <p className="text-sm text-gray-600 dark:text-gray-400">
          23 actions per minute
        </p>

        {batch.current_item_name && (
          <p className="text-sm">
            <span className="text-gray-600 dark:text-gray-400">
              Current Item:
            </span>{' '}
            {renderCurrentItem()}
          </p>
        )}

        {disciplineLabel && (
          <p className="text-sm">
            <span className="text-gray-600 dark:text-gray-400">
              Current Crafting Discipline:
            </span>{' '}
            <span className="font-semibold">{disciplineLabel}</span>
          </p>
        )}
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Results">
        <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Crafted</dt>
          <dd>{batch.crafted_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Failed</dt>
          <dd>{batch.failed_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Skipped</dt>
          <dd>{batch.skipped_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Crafting XP Gained
          </dt>
          <dd>{(event?.crafting_xp_gained ?? 0).toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Spent</dt>
          <dd>{batch.gold_spent.toLocaleString()}</dd>
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

      {!isRunning && batch.ended_reason && (
        <BatchCraftingDetailSection title="Completion">
          <Alert variant={AlertVariant.INFO}>
            {endReasonLabel(batch.ended_reason)}. Final contribution:{' '}
            {(event?.character_contribution ?? 0).toLocaleString()}, goal
            progress {(event?.total_crafts ?? 0).toLocaleString()} /{' '}
            {event?.max_crafts?.toLocaleString() ?? '—'}. Crafted{' '}
            {batch.crafted_count.toLocaleString()}, failed{' '}
            {batch.failed_count.toLocaleString()}, skipped{' '}
            {batch.skipped_count.toLocaleString()}, gained{' '}
            {(event?.crafting_xp_gained ?? 0).toLocaleString()} Crafting XP.
          </Alert>
        </BatchCraftingDetailSection>
      )}
    </div>
  );
};

export default CraftEventRunningSection;
