import React, { ReactNode } from 'react';

import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { useOpenBatchCraftedItem } from '../../hooks/use-open-batch-crafted-item';
import {
  endReasonLabel,
  eventEnchantPhaseLabel,
} from '../../utils/batch-crafting-labels';
import { getBatchCraftedItemLinkLabel } from '../../utils/get-batch-crafted-item-link-label';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';

const EnchantEventRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const event = batch.event_progress;
  const isRunning = batch.status === BatchCraftingStatus.RUNNING;
  const { openBatchCraftedItem } = useOpenBatchCraftedItem({ character_id });

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

  const enchantingXpGained = event?.enchanting_xp_gained ?? 0;
  const craftingXpGained = event?.crafting_xp_gained ?? 0;

  return (
    <div className="space-y-3">
      <BatchCraftingDetailSection title="Progress">
        {event &&
          event.max_enchants !== null &&
          event.max_enchants !== undefined && (
            <ProgressBar
              value={event.total_enchants ?? 0}
              max={event.max_enchants}
              label="Event Goal Progress"
              value_label={`${event.total_enchants ?? 0} / ${event.max_enchants}`}
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
          {event?.phase && ` · ${eventEnchantPhaseLabel(event.phase)}`}
        </p>

        {batch.current_item_name && (
          <p className="text-sm">
            <span className="text-gray-600 dark:text-gray-400">
              Current Item:
            </span>{' '}
            {renderCurrentItem()}
          </p>
        )}

        {(batch.current_prefix_name || batch.current_suffix_name) && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {[batch.current_prefix_name, batch.current_suffix_name]
              .filter(Boolean)
              .join(' · ')}
          </p>
        )}
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Results">
        <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Applied</dt>
          <dd>{batch.applied_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Failed</dt>
          <dd>{batch.failed_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Skipped</dt>
          <dd>{batch.skipped_count}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Enchanting XP Gained
          </dt>
          <dd>{enchantingXpGained.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Crafting XP Gained
          </dt>
          <dd>{craftingXpGained.toLocaleString()}</dd>
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
            progress {(event?.total_enchants ?? 0).toLocaleString()} /{' '}
            {event?.max_enchants?.toLocaleString() ?? '—'}. Applied{' '}
            {batch.applied_count.toLocaleString()}, failed{' '}
            {batch.failed_count.toLocaleString()}, skipped{' '}
            {batch.skipped_count.toLocaleString()}, gained{' '}
            {enchantingXpGained.toLocaleString()} Enchanting XP and{' '}
            {craftingXpGained.toLocaleString()} Crafting XP.
          </Alert>
        </BatchCraftingDetailSection>
      )}
    </div>
  );
};

export default EnchantEventRunningSection;
