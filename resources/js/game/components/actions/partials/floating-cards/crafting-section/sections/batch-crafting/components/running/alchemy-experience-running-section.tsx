import React, { ReactNode } from 'react';

import { BatchCraftingDisposition } from '../../enums/batch-crafting-disposition';
import { useOpenBatchCraftedAlchemyItem } from '../../hooks/use-open-batch-crafted-alchemy-item';
import BatchCraftingDetailSection from '../batch-crafting-detail-section';
import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import BatchCraftingRunningSectionProps from './types/batch-crafting-running-section-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

const AlchemyExperienceRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const { openBatchCraftedAlchemyItem } = useOpenBatchCraftedAlchemyItem({
    character_id,
  });
  const progress = batch.alchemy_experience_progress;

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
      <BatchCraftingDetailSection title="Progress">
        {renderCurrentItem()}

        <p className="text-sm text-gray-600 dark:text-gray-400">
          {progress.actions_per_minute} actions per minute
        </p>

        {progress.alchemy_skill && (
          <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-3 gap-y-1 text-sm">
            <dt className="text-gray-600 dark:text-gray-400">Level</dt>
            <dd>{progress.alchemy_skill.level}</dd>
            <dt className="text-gray-600 dark:text-gray-400">Experience</dt>
            <dd>
              {progress.alchemy_skill.current_xp.toLocaleString()} /{' '}
              {progress.alchemy_skill.next_level_xp.toLocaleString()}
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
            Alchemy XP Gained
          </dt>
          <dd>{progress.alchemy_xp_gained.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Dust Spent</dt>
          <dd>{batch.gold_dust_spent.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Gold Dust Left</dt>
          <dd>{batch.gold_dust_left.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Shards Spent</dt>
          <dd>{batch.shards_spent.toLocaleString()}</dd>
          <dt className="text-gray-600 dark:text-gray-400">Shards Left</dt>
          <dd>{batch.shards_left.toLocaleString()}</dd>
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

export default AlchemyExperienceRunningSection;
