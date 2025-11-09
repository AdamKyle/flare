import React from 'react';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';
import ManageGuideQuestStepProps from "./types/manage-guide-quest-step-props";
import {useManageFormSectionData} from "../hooks/use-manage-form-section-data";

const ManageGuideQuestsRequiredKingdomAttributes = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const {
    handleUpdateFormData,
    convertObjectToKeyValue
  } = useManageFormSectionData({
    on_update,
  });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Total Kingdoms Owned
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_kingdoms', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Level
        </label>
        <p className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          This is the total level for all kingdoms you own.
        </p>
        <Input
          on_change={(value) => handleUpdateFormData('required_kingdom_level', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Building
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.kingdom_buildings)}
          on_select={(value) => handleUpdateFormData('required_kingdom_building_id', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Building Level
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_kingdom_building_level', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Units
        </label>
        <p className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          This is the total units across all kingdoms
        </p>
        <Input
          on_change={(value) => handleUpdateFormData('required_kingdom_units', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Passive
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.passives)}
          on_select={(value) => handleUpdateFormData('required_passive_skill', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Passive Level
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_passive_skill_level', value)}
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredKingdomAttributes;