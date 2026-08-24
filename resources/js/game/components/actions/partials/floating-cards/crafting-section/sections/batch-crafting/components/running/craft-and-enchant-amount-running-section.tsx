import React, { ReactNode } from 'react';

import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { useOpenBatchCraftedItem } from '../../hooks/use-open-batch-crafted-item';
import { getBatchCraftedItemLinkLabel } from '../../utils/get-batch-crafted-item-link-label';
import BatchCraftingCompletionSummary from '../batch-crafting-completion-summary';
import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import CraftAmountMetrics from '../craft-amount-metrics';
import CraftAmountProgress from '../craft-amount-progress';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const CraftAndEnchantAmountRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const requestedAmount = batch.requested_amount ?? 0;
  const completedAmount = batch.completed_amount ?? 0;
  const remainingAmount = batch.remaining_amount;
  const isRunning = batch.status === BatchCraftingStatus.RUNNING;
  const { openBatchCraftedItem } = useOpenBatchCraftedItem({
    character_id,
  });

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

    return <p className="font-semibold">{batch.current_item_name}</p>;
  };

  return (
    <div className="space-y-3">
      <BatchCraftingDetailSection title="Progress">
        {renderCurrentItem()}

        {(batch.current_prefix_name || batch.current_suffix_name) && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {[batch.current_prefix_name, batch.current_suffix_name]
              .filter(Boolean)
              .join(' · ')}
          </p>
        )}

        <CraftAmountProgress
          requested_amount={requestedAmount}
          completed_amount={completedAmount}
          output_destination={batch.output_destination}
          destination_capacity={batch.destination_capacity}
        />
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Results">
        <CraftAmountMetrics
          disposition={batch.disposition}
          successful={completedAmount}
          failed={batch.failed_count}
          remaining={remainingAmount}
          gold_spent={batch.gold_spent}
          gold_gained={batch.gold_gained}
          gold_left={batch.gold_left}
        />

        {batch.listing_price !== null && (
          <p className="text-sm">
            <span className="text-gray-600 dark:text-gray-400">
              Listing Price:
            </span>{' '}
            {batch.listing_price.toLocaleString()} Gold
          </p>
        )}
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Activity">
        <BatchCraftingOutcomeChart
          chart_points={batch.chart_points}
          processing_started_at={batch.processing_started_at}
        />
      </BatchCraftingDetailSection>

      {!isRunning && batch.ended_reason && (
        <BatchCraftingDetailSection title="Completion">
          <BatchCraftingCompletionSummary
            end_reason={batch.ended_reason}
            requested={batch.requested_amount}
            completed={completedAmount}
            remaining={remainingAmount}
          />
        </BatchCraftingDetailSection>
      )}
    </div>
  );
};

export default CraftAndEnchantAmountRunningSection;
