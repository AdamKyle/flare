import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRequiredCurrencies = ({
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
          Required Gold
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_gold)}
          on_change={(value) => handleUpdateFormData('required_gold', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Gold Dust
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_gold_dust)}
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
          default_value={getDefaultString(guideQuest?.required_shards)}
          on_change={(value) => handleUpdateFormData('required_shards', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Copper Coins
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_copper_coins)}
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
          default_value={getDefaultString(guideQuest?.required_gold_bars)}
          on_change={(value) =>
            handleUpdateFormData('required_gold_bars', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredCurrencies;
