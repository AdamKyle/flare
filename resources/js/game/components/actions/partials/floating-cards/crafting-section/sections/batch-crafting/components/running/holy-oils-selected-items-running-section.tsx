import React, { ReactNode } from 'react';

import { useOpenBatchCraftedAlchemyItem } from '../../hooks/use-open-batch-crafted-alchemy-item';
import { useOpenBatchHolyOilInventoryTarget } from '../../hooks/use-open-batch-holy-oil-inventory-target';
import BatchCraftingDetailSection from '../batch-crafting-detail-section';
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

  const currentTargetItemName = progress.current_target_item_name;
  const currentOilItemName = progress.current_oil_item_name;

  return (
    <div className="space-y-3">
      <BatchCraftingDetailSection title="Progress">
        {currentTargetItemName && (
          <LinkButton
            label={currentTargetItemName}
            variant={ButtonVariant.PRIMARY}
            on_click={() =>
              openBatchHolyOilInventoryTarget(currentTargetItemName)
            }
            aria_label={`Open ${currentTargetItemName} in your Backpack`}
            additional_css="font-semibold"
          />
        )}

        {currentOilItemName && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Applying:{' '}
            <LinkButton
              label={currentOilItemName}
              variant={ButtonVariant.PRIMARY}
              on_click={() => openBatchCraftedAlchemyItem(currentOilItemName)}
              aria_label={`Open ${currentOilItemName} in your Alchemy Bag`}
            />
          </p>
        )}

        {progress.max_holy_stacks !== null && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Holy Stacks: {progress.current_holy_stacks} /{' '}
            {progress.max_holy_stacks}
          </p>
        )}
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Results">
        <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Total Targets</dt>
          <dd>{progress.total_planned_targets.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Completed Targets
          </dt>
          <dd>{progress.completed_targets.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Remaining Targets
          </dt>
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
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Activity">
        <BatchCraftingOutcomeChart
          chart_points={batch.chart_points}
          processing_started_at={batch.processing_started_at}
        />
      </BatchCraftingDetailSection>
    </div>
  );
};

export default HolyOilsSelectedItemsRunningSection;
