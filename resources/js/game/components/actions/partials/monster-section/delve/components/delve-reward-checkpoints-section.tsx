import clsx from 'clsx';
import React, { ReactNode } from 'react';

import DelveRewardCheckpointsSectionProps from './types/delve-reward-checkpoints-section-props';
import ExplorationDetailSection from '../../exploration/components/exploration-detail-section';
import DelveRewardCheckpointDefinition from '../api/definitions/delve-reward-checkpoint-definition';

const DelveRewardCheckpointsSection = ({
  reward_checkpoints: rewardCheckpoints,
}: DelveRewardCheckpointsSectionProps): ReactNode => {
  const renderSpecialItem = (
    checkpoint: DelveRewardCheckpointDefinition
  ): ReactNode => {
    if (checkpoint.special_item === null) {
      return null;
    }

    return <span>, plus a {checkpoint.special_item} item</span>;
  };

  const renderCheckpoint = (checkpoint: DelveRewardCheckpointDefinition) => (
    <li
      key={checkpoint.label}
      className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"
    >
      <div className="min-w-0">
        <p className="font-medium text-gray-900 dark:text-gray-100">
          {checkpoint.label}{' '}
          <span className="text-sm text-gray-600 dark:text-gray-400">
            ({checkpoint.requirement})
          </span>
        </p>
        <p className="text-sm text-gray-700 dark:text-gray-300">
          {checkpoint.gold} Gold
          {renderSpecialItem(checkpoint)}
        </p>
      </div>
      <span
        className={clsx(
          'shrink-0 text-sm font-semibold',
          checkpoint.reached
            ? 'text-emerald-700 dark:text-emerald-400'
            : 'text-gray-600 dark:text-gray-400'
        )}
      >
        <i
          className={clsx(
            'mr-1',
            checkpoint.reached ? 'fas fa-check-circle' : 'far fa-circle'
          )}
          aria-hidden="true"
        />
        {checkpoint.reached ? 'Reached' : 'Not reached'}
      </span>
    </li>
  );

  return (
    <ExplorationDetailSection title="Reward Checkpoints">
      <ul className="space-y-3">{rewardCheckpoints.map(renderCheckpoint)}</ul>
    </ExplorationDetailSection>
  );
};

export default DelveRewardCheckpointsSection;
