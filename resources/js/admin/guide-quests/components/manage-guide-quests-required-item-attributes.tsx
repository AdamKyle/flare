import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredItemAttributes = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData, convertObjectToKeyValue, getPreSelected } =
    useManageFormSectionData({
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

  const specialtyItems = convertObjectToKeyValue(
    data_for_component.item_specialty_types
  );

  const preSelectedSpecialty = getPreSelected(
    specialtyItems,
    guideQuest?.required_specialty_type
  );

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Passive
        </label>
        <Dropdown
          items={specialtyItems}
          pre_selected_item={preSelectedSpecialty}
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
          default_value={getDefaultString(guideQuest?.required_holy_stacks)}
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
          default_value={getDefaultString(guideQuest?.required_attached_gems)}
          on_change={(value) =>
            handleUpdateFormData('required_attached_gems', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredItemAttributes;
