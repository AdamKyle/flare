import React, { ReactNode } from 'react';

import CurrencyDisplay from '../../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../../reusable-components/currency/enums/currency-type';
import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { BatchCraftingStatus } from '../../enums/batch-crafting-status';
import { useOpenBatchCraftedAlchemyItem } from '../../hooks/use-open-batch-crafted-alchemy-item';
import BatchCraftingCompletionSummary from '../batch-crafting-completion-summary';
import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const AlchemyAmountRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const { openBatchCraftedAlchemyItem } = useOpenBatchCraftedAlchemyItem({
    character_id,
  });
  const progress = batch.alchemy_amount_progress;
  const isRunning = batch.status === BatchCraftingStatus.RUNNING;

  if (!progress) {
    return null;
  }

  const currentItemName = progress.current_item_name;

  const renderCurrentItem = () => {
    if (!currentItemName) {
      return null;
    }

    if (batch.disposition !== BatchCraftingDisposition.KEEP) {
      return <p className="font-semibold">{currentItemName}</p>;
    }

    return (
      <LinkButton
        label={currentItemName}
        variant={ButtonVariant.PRIMARY}
        on_click={() => openBatchCraftedAlchemyItem(currentItemName)}
        aria_label={`Open ${currentItemName} in your Alchemy Bag`}
      />
    );
  };

  return (
    <div className="space-y-3">
      {currentItemName && (
        <BatchCraftingDetailSection title="Progress">
          {renderCurrentItem()}
        </BatchCraftingDetailSection>
      )}

      <BatchCraftingDetailSection title="Results">
        <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Requested</dt>
          <dd>{progress.requested_amount.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Completed</dt>
          <dd>{progress.completed_amount.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Remaining</dt>
          <dd>{progress.remaining_amount.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Failed</dt>
          <dd>{batch.failed_count.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Alchemy XP Gained
          </dt>
          <dd>{progress.alchemy_xp_gained.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Dust Spent</dt>
          <dd>
            <CurrencyDisplay
              currency={CurrencyType.GOLD_DUST}
              amount={batch.gold_dust_spent}
              display_mode={CurrencyDisplayMode.EXACT}
              show_label={false}
            />
          </dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Dust Left</dt>
          <dd>
            <CurrencyDisplay
              currency={CurrencyType.GOLD_DUST}
              amount={batch.gold_dust_left}
              display_mode={CurrencyDisplayMode.BALANCE}
              show_label={false}
            />
          </dd>
          <dt className="text-gray-600 dark:text-gray-400">Shards Spent</dt>
          <dd>
            <CurrencyDisplay
              currency={CurrencyType.SHARDS}
              amount={batch.shards_spent}
              display_mode={CurrencyDisplayMode.EXACT}
              show_label={false}
            />
          </dd>
          <dt className="text-gray-600 dark:text-gray-400">Shards Left</dt>
          <dd>
            <CurrencyDisplay
              currency={CurrencyType.SHARDS}
              amount={batch.shards_left}
              display_mode={CurrencyDisplayMode.BALANCE}
              show_label={false}
            />
          </dd>
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
          <BatchCraftingCompletionSummary
            end_reason={batch.ended_reason}
            requested={progress.requested_amount}
            completed={progress.completed_amount}
            remaining={progress.remaining_amount}
          />
        </BatchCraftingDetailSection>
      )}
    </div>
  );
};

export default AlchemyAmountRunningSection;
