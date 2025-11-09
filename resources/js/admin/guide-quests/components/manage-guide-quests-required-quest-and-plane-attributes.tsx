import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredQuestAndPlaneAttributes = ({
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
          Required Plane Access
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.game_maps)}
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
          items={convertObjectToKeyValue(data_for_component.game_maps)}
          on_select={(value) => handleUpdateFormData('be_on_game_map', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Quest Completed
        </label>
        <Dropdown
          items={convertObjectToKeyValue(data_for_component.quests)}
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
          items={convertObjectToKeyValue(data_for_component.quest_items)}
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
          items={convertObjectToKeyValue(data_for_component.quest_items)}
          on_select={(value) =>
            handleUpdateFormData('secondary_quest_item_id', value)
          }
        />
      </div>

      <div className="my-4 flex items-center">
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700"></span>
        <span className="px-3 text-sm text-gray-500 dark:text-gray-400">
          Event Goals Participation
        </span>
        <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700"></span>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Event Goal Kill Count
        </label>
        <Input
          on_change={(value) =>
            handleUpdateFormData('required_event_goal_participation', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredQuestAndPlaneAttributes;
