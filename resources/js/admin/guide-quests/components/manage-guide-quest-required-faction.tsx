import ManageGuideQuestStepProps from "./types/manage-guide-quest-step-props";
import {useManageFormSectionData} from "../hooks/use-manage-form-section-data";

import React from "react";
import Dropdown from "ui/drop-down/drop-down";
import Input from "ui/input/input";

const ManageGuideQuestRequiredFaction = ({data_for_component, on_update}: ManageGuideQuestStepProps) => {
  const {convertObjectToKeyValue, handleUpdateFormData} = useManageFormSectionData({ on_update });

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Faction
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.game_skills)}
          on_select={(value) => handleUpdateFormData('required_faction_id', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Faction Level
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_faction_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Faction Level
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_fame_level', value)
          }
        />
      </div>

    </div>
  )
}

export default ManageGuideQuestRequiredFaction;