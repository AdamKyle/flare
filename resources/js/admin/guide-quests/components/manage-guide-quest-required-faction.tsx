import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestRequiredFaction = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const { convertObjectToKeyValue, handleUpdateFormData, getPreSelected } =
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

  const factionItems = convertObjectToKeyValue(data_for_component.faction_maps);
  const preSelectedFaction = getPreSelected(
    factionItems,
    guideQuest?.required_faction_id
  );

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Faction
        </label>
        <Dropdown
          items={factionItems}
          pre_selected_item={preSelectedFaction}
          on_select={(value) =>
            handleUpdateFormData('required_faction_id', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Faction Level
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_faction_level)}
          on_change={(value) =>
            handleUpdateFormData('required_faction_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Fame Level
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_fame_level)}
          on_change={(value) =>
            handleUpdateFormData('required_fame_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestRequiredFaction;
