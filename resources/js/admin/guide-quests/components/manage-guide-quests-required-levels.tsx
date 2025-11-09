import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredLevels = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const {
    convertObjectToKeyValue,
    handleUpdateFormData,
    convertArrayToDropDown,
  } = useManageFormSectionData({
    on_update,
  });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Level
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_level', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.game_skills)}
          on_select={(value) => handleUpdateFormData('required_skill', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Level
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_skill_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Secondary Required Skill
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.game_skills)}
          on_select={(value) =>
            handleUpdateFormData('required_secondary_skill', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Secondary Required Skill Level
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_secondary_skill_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Type
        </label>
        <Dropdown
          items={convertArrayToDropDown(data_for_component.skill_types)}
          on_select={(value) =>
            handleUpdateFormData('required_skill_type', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Type Level
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_skill_type_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredLevels;
