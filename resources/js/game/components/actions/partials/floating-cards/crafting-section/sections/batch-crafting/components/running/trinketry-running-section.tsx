import React, { ReactNode } from 'react';

import { useOpenBatchCraftingSet } from '../../hooks/use-open-batch-crafting-set';
import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const TrinketryRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const progress = batch.trinketry_progress;
  const { openBatchCraftingSet } = useOpenBatchCraftingSet({ character_id });

  if (!progress) {
    return null;
  }

  const renderCurrentItem = () => {
    const currentItemName = progress.current_item_name;

    if (!currentItemName) {
      return null;
    }

    const destinationSetId = progress.destination_set_id;
    const destinationSetName = progress.destination_set_name;

    if (destinationSetId === null || destinationSetName === null) {
      return <p className="font-semibold">{currentItemName}</p>;
    }

    return (
      <LinkButton
        label={currentItemName}
        variant={ButtonVariant.PRIMARY}
        on_click={() =>
          openBatchCraftingSet(
            destinationSetId,
            destinationSetName,
            currentItemName
          )
        }
        aria_label={`Open ${currentItemName} in ${destinationSetName}`}
        additional_css="font-semibold"
      />
    );
  };

  return (
    <div className="space-y-3">
      <BatchCraftingDetailSection title="Progress">
        {renderCurrentItem()}

        <p className="text-sm text-gray-600 dark:text-gray-400">
          {progress.actions_per_minute} actions per minute
        </p>

        {progress.trinketry_skill && (
          <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
            <dt className="text-gray-600 dark:text-gray-400">Level</dt>
            <dd>{progress.trinketry_skill.level}</dd>
            <dt className="text-gray-600 dark:text-gray-400">Experience</dt>
            <dd>
              {progress.trinketry_skill.current_xp.toLocaleString()} /{' '}
              {progress.trinketry_skill.next_level_xp.toLocaleString()}
            </dd>
          </dl>
        )}
      </BatchCraftingDetailSection>

      <BatchCraftingDetailSection title="Results">
        <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
          <dt className="text-gray-600 dark:text-gray-400">Successful</dt>
          <dd>{batch.crafted_count.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Failed</dt>
          <dd>{batch.failed_count.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Trinketry XP Gained
          </dt>
          <dd>{progress.trinketry_xp_gained.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Dust Spent</dt>
          <dd>{batch.gold_dust_spent.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Dust Left</dt>
          <dd>{batch.gold_dust_left.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Copper Coins Spent
          </dt>
          <dd>{batch.copper_coins_spent.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">
            Copper Coins Left
          </dt>
          <dd>{batch.copper_coins_left.toLocaleString()}</dd>
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

export default TrinketryRunningSection;
