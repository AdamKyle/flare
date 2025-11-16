import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredKingdomAttributes = ({
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

  const kingdomBuildingItems = convertObjectToKeyValue(
    data_for_component.kingdom_buildings
  );
  const preSelectedKingdomBuilding = getPreSelected(
    kingdomBuildingItems,
    guideQuest?.required_kingdom_building_id
  );

  const passiveItems = convertObjectToKeyValue(data_for_component.passives);
  const preSelectedPassive = getPreSelected(
    passiveItems,
    guideQuest?.required_passive_skill
  );

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Total Kingdoms Owned
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_kingdoms)}
          on_change={(value) =>
            handleUpdateFormData('required_kingdoms', value)
          }
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
          default_value={getDefaultString(guideQuest?.required_kingdom_level)}
          on_change={(value) =>
            handleUpdateFormData('required_kingdom_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Building
        </label>
        <Dropdown
          items={kingdomBuildingItems}
          pre_selected_item={preSelectedKingdomBuilding}
          on_select={(value) =>
            handleUpdateFormData('required_kingdom_building_id', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Building Level
        </label>
        <Input
          default_value={getDefaultString(
            guideQuest?.required_kingdom_building_level
          )}
          on_change={(value) =>
            handleUpdateFormData('required_kingdom_building_level', value)
          }
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
          default_value={getDefaultString(guideQuest?.required_kingdom_units)}
          on_change={(value) =>
            handleUpdateFormData('required_kingdom_units', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Passive
        </label>
        <Dropdown
          items={passiveItems}
          pre_selected_item={preSelectedPassive}
          on_select={(value) =>
            handleUpdateFormData('required_passive_skill', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Kingdom Passive Level
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_passive_level)}
          on_change={(value) =>
            handleUpdateFormData('required_passive_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredKingdomAttributes;
