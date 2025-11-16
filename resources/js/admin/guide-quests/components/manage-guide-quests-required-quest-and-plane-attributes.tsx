import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredQuestAndPlaneAttributes = ({
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

  const mapItems = convertObjectToKeyValue(data_for_component.game_maps);
  const preSelectedRequiredMap = getPreSelected(
    mapItems,
    guideQuest?.required_game_map_id
  );
  const preSelectedBeOnMap = getPreSelected(
    mapItems,
    guideQuest?.be_on_game_map
  );

  const questItems = convertObjectToKeyValue(data_for_component.quests);
  const preSelectedQuest = getPreSelected(
    questItems,
    guideQuest?.required_quest_id
  );

  const questItemItems = convertObjectToKeyValue(
    data_for_component.quest_items
  );
  const preSelectedQuestItem = getPreSelected(
    questItemItems,
    guideQuest?.required_quest_item_id
  );
  const preSelectedSecondaryQuestItem = getPreSelected(
    questItemItems,
    guideQuest?.secondary_quest_item_id
  );

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Plane Access
        </label>
        <Dropdown
          items={mapItems}
          pre_selected_item={preSelectedRequiredMap}
          on_select={(value) =>
            handleUpdateFormData('required_game_map_id', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Physically Be on plane
        </label>
        <Dropdown
          items={mapItems}
          pre_selected_item={preSelectedBeOnMap}
          on_select={(value) => handleUpdateFormData('be_on_game_map', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Quest Completed
        </label>
        <Dropdown
          items={questItems}
          pre_selected_item={preSelectedQuest}
          on_select={(value) =>
            handleUpdateFormData('required_quest_id', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Quest Item
        </label>
        <Dropdown
          items={questItemItems}
          pre_selected_item={preSelectedQuestItem}
          on_select={(value) =>
            handleUpdateFormData('required_quest_item_id', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Secondary Quest Item
        </label>
        <Dropdown
          items={questItemItems}
          pre_selected_item={preSelectedSecondaryQuestItem}
          on_select={(value) =>
            handleUpdateFormData('secondary_quest_item_id', value)
          }
        />
      </div>

      <div className="my-4 flex items-center">
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700" />
        <span className="px-3 text-sm text-gray-500 dark:text-gray-400">
          Event Goals Participation
        </span>
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700" />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Event Goal Kill Count
        </label>
        <Input
          default_value={getDefaultString(
            guideQuest?.required_event_goal_participation
          )}
          on_change={(value) =>
            handleUpdateFormData('required_event_goal_participation', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredQuestAndPlaneAttributes;
