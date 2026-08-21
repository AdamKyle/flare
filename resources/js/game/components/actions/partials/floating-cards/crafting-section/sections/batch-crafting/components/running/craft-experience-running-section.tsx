import React, { ReactNode } from 'react';

import BatchCraftingOutcomeChart from '../batch-crafting-outcome-chart';
import CraftingSkillProgressList from '../crafting-skill-progress-list';
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
import Separator from 'ui/separator/separator';

const CraftExperienceRunningSection = ({
  batch,
  character_id,
}: BatchCraftingRunningSectionProps): ReactNode => {
  const experience = batch.experience_progress;
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

  const disciplineLabel = craftingDisciplineLabel(
    experience?.current_crafting_type ?? null
  );

  return (
    <div className="space-y-3">
      {renderCurrentItem()}

      {disciplineLabel && (
        <p className="text-sm">
          <span className="text-gray-600 dark:text-gray-400">
            Current Crafting Discipline:
          </span>{' '}
          <span className="font-semibold">{disciplineLabel}</span>
        </p>
      )}

      <p className="text-sm text-gray-600 dark:text-gray-400">
        6 actions per minute
        {experience &&
          ` · Cycle position ${experience.current_cycle_position + 1} of ${experience.cycle_size}`}
      </p>

      {experience && (
        <CraftingSkillProgressList skills={experience.crafting_skills} />
      )}

      <Separator />

      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
        <dt className="text-gray-600 dark:text-gray-400">Successful</dt>
        <dd>{batch.crafted_count}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Failed</dt>
        <dd>{batch.failed_count}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Crafting XP Gained</dt>
        <dd>{(experience?.crafting_xp_gained ?? 0).toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Gold Spent</dt>
        <dd>{batch.gold_spent.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Gold Gained</dt>
        <dd>{batch.gold_gained.toLocaleString()}</dd>
        <dt className="text-gray-600 dark:text-gray-400">Gold Left</dt>
        <dd>{batch.gold_left.toLocaleString()}</dd>
      </dl>

      <BatchCraftingOutcomeChart
        chart_points={batch.chart_points}
        processing_started_at={batch.processing_started_at}
      />

      {!isRunning && batch.ended_reason && (
        <Alert variant={AlertVariant.INFO}>
          {endReasonLabel(batch.ended_reason)}. Crafted{' '}
          {batch.crafted_count.toLocaleString()}, failed{' '}
          {batch.failed_count.toLocaleString()}, gained{' '}
          {(experience?.crafting_xp_gained ?? 0).toLocaleString()} Crafting XP.
        </Alert>
      )}
    </div>
  );
};

export default CraftExperienceRunningSection;
