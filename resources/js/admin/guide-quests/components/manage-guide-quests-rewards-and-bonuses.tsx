import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRewardsAndBonuses = ({
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData } = useManageFormSectionData({
    on_update,
  });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Extra Faction Points Per Kill
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('faction_points_per_kill', value)
          }
        />
      </div>

      <div className="my-4 flex items-center">
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700"></span>
        <span className="px-3 text-sm text-gray-500 dark:text-gray-400">
          Reward Section
        </span>
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700"></span>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          XP Reward
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('xp_reward', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Gold Reward
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('gold_reward', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Gold Dust Rewards
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('gold_dust_reward', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Shards Reward
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('shards_reward', value)}
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRewardsAndBonuses;
