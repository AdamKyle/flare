import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Input from 'ui/input/input';

const ManageGuideQuestsRequiredClassRanksAttributes = ({
  on_update,
}: ManageGuideQuestStepProps) => {
  const { handleUpdateFormData } = useManageFormSectionData({
    on_update,
  });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Total Class Specialities Equipped
        </label>
        <Input
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
          on_change={(value) =>
            handleUpdateFormData('required_class_rank_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredClassRanksAttributes;
