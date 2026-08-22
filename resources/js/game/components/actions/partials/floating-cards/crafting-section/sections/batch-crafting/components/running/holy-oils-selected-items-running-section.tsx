import React, { ReactNode } from 'react';

import { useOpenBatchCraftedAlchemyItem } from '../../hooks/use-open-batch-crafted-alchemy-item';
import { useOpenBatchHolyOilInventoryTarget } from '../../hooks/use-open-batch-holy-oil-inventory-target';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const HolyOilsSelectedItemsRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const progress = batch.holy_oils_progress;
  const { openBatchHolyOilInventoryTarget } =
    useOpenBatchHolyOilInventoryTarget();
  const { openBatchCraftedAlchemyItem } = useOpenBatchCraftedAlchemyItem({
    character_id,
  });

  if (!progress) {
    return null;
  }

  return (
    <div className="space-y-3">
      {progress.current_target_item_name && (
        <LinkButton
          label={progress.current_target_item_name}
          variant={ButtonVariant.PRIMARY}
          on_click={() =>
            openBatchHolyOilInventoryTarget(
              progress.current_target_item_name as string
            )
          }
          aria_label={`Open ${progress.current_target_item_name} in your Backpack`}
          additional_css="font-semibold"
        />
      )}

      {progress.current_oil_item_name && (
        <p className="text-sm text-gray-600 dark:text-gray-400">
          Applying:{' '}
          <LinkButton
            label={progress.current_oil_item_name}
            variant={ButtonVariant.PRIMARY}
            on_click={() =>
              openBatchCraftedAlchemyItem(
                progress.current_oil_item_name as string
              )
            }
            aria_label={`Open ${progress.current_oil_item_name} in your Alchemy Bag`}
          />
        </p>
      )}

      {progress.max_holy_stacks !== null && (
        <p className="text-sm text-gray-600 dark:text-gray-400">
          Holy Stacks: {progress.current_holy_stacks} /{' '}
          {progress.max_holy_stacks}
        </p>
      )}

      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
        <dt className="text-gray-600 dark:text-gray-400">Total Targets</dt>
        <dd>{progress.total_planned_targets.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Completed Targets</dt>
        <dd>{progress.completed_targets.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Remaining Targets</dt>
        <dd>{progress.remaining_targets.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Applications</dt>
        <dd>{batch.applied_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Kept</dt>
        <dd>{batch.kept_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Sold</dt>
        <dd>{batch.sold_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Destroyed</dt>
        <dd>{batch.destroyed_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Listed</dt>
        <dd>{batch.listed_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Disenchanted</dt>
        <dd>{batch.disenchanted_count.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Gold Dust Spent</dt>
        <dd>{batch.gold_dust_spent.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Gold Dust Left</dt>
        <dd>{batch.gold_dust_left.toLocaleString()}</dd>
      </dl>

      <BatchCraftingOutcomeChart
        chart_points={batch.chart_points}
        processing_started_at={batch.processing_started_at}
      />
    </div>
  );
};

export default HolyOilsSelectedItemsRunningSection;
