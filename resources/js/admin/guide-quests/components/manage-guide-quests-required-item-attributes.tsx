import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredItemAttributes = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData, convertObjectToKeyValue } =
    useManageFormSectionData({
      on_update,
    });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Passive
        </label>
        <Dropdown
          items={convertObjectToKeyValue(
            data_for_component.item_specialty_types
          )}
          on_select={(value) =>
            handleUpdateFormData('required_specialty_type', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Holy Stacks Level
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_holy_stacks', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Gems Attached
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_attached_gems', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredItemAttributes;
