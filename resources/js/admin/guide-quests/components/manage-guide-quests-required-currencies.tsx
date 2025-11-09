import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRequiredCurrencies = ({
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData } = useManageFormSectionData({
    on_update,
  });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Gold
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_gold', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Gold Dust
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_gold_dust', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Shards
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_shards', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Copper Coins
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_copper_coins', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Gold Bars
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_gold_bars', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredCurrencies;
