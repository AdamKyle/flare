import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRequiredStats = ({
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData } = useManageFormSectionData({
    on_update,
  });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Total
        </label>
        <p className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          This is the total of all the stats added together
        </p>
        <Input
          on_change={(value) => handleUpdateFormData('required_stats', value)}
        />
      </div>

      <div className="my-4 flex items-center">
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700"></span>
        <span className="px-3 text-sm text-gray-500 dark:text-gray-400">
          Or
        </span>
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700"></span>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Strength (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_str', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Dexterity (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_dex', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Intelligence (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_int', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Agility (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_agi', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Durability (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_dur', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Charisma (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_chr', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Focus (Total)
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_focus', value)}
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredStats;
