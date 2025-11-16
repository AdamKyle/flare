import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRewardsAndBonuses = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData } = useManageFormSectionData({
    on_update,
    initial_values: data_for_component.guide_quest,
  });

  const guideQuest = data_for_component.guide_quest ?? null;

  const getDefaultString = (candidate?: number | string | null): string => {
    if (!candidate) {
      return '';
    }

    return String(candidate);
  };

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Extra Faction Points Per Kill
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.faction_points_per_kill)}
          on_change={(value) =>
            handleUpdateFormData('faction_points_per_kill', value)
          }
        />
      </div>

      <div className="my-4 flex items-center">
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700" />
        <span className="px-3 text-sm text-gray-500 dark:text-gray-400">
          Reward Section
        </span>
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700" />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          XP Reward
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.xp_reward)}
          on_change={(value) => handleUpdateFormData('xp_reward', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Gold Reward
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.gold_reward)}
          on_change={(value) => handleUpdateFormData('gold_reward', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Gold Dust Rewards
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.gold_dust_reward)}
          on_change={(value) => handleUpdateFormData('gold_dust_reward', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Shards Reward
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.shards_reward)}
          on_change={(value) => handleUpdateFormData('shards_reward', value)}
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRewardsAndBonuses;
