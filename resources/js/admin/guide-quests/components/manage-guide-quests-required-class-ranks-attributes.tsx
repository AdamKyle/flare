import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRequiredClassRanksAttributes = ({
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
          Required Total Class Specialities Equipped
        </label>
        <Input
          default_value={getDefaultString(
            guideQuest?.required_class_specials_equipped
          )}
          on_change={(value) =>
            handleUpdateFormData('required_class_specials_equipped', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Class Rank level
        </label>
        <Input
          default_value={getDefaultString(
            guideQuest?.required_class_rank_level
          )}
          on_change={(value) =>
            handleUpdateFormData('required_class_rank_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredClassRanksAttributes;
