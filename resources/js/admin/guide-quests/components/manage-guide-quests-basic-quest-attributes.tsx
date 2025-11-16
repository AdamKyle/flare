import React from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Dropdown from 'ui/drop-down/drop-down';
import Input from 'ui/input/input';

const ManageGuideQuestsBasicQuestAttributes = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const {
    handleUpdateFormData,
    convertObjectToKeyValue,
    convertArrayToDropDown,
    getPreSelected,
  } = useManageFormSectionData({
    on_update,
    initial_values: data_for_component.guide_quest,
  });

  const guideQuest = data_for_component.guide_quest ?? null;

  const getDefaultUnlockLevel = (): string => {
    if (!guideQuest) {
      return '';
    }

    const level = guideQuest.required_level;

    if (!level) {
      return '';
    }

    return String(level);
  };

  const renderParentGuideQuestOption = () => {
    if (!data_for_component.guide_quests) {
      return null;
    }

    const parentItems = convertObjectToKeyValue(
      data_for_component.guide_quests
    );

    const preSelectedParent = getPreSelected(
      parentItems,
      guideQuest?.parent_id
    );

    return (
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Belongs to Parent Guide Quest
        </label>
        <Dropdown
          items={parentItems}
          pre_selected_item={preSelectedParent}
          on_select={(value) => handleUpdateFormData('parent_id', value)}
        />
      </div>
    );
  };

  const eventItems = convertArrayToDropDown(data_for_component.events ?? []);

  const preSelectedEvent = getPreSelected(
    eventItems,
    guideQuest?.only_during_event
  );

  return (
    <div className="space-y-4">
      <div>
        <div>
          <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
            Guide Quest Name
          </label>
          <Input
            default_value={guideQuest?.name ?? ''}
            on_change={(value) => handleUpdateFormData('name', value)}
          />
        </div>

        <div className="my-4 flex items-center">
          <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700" />
          <span className="px-3 text-sm text-gray-500 dark:text-gray-400">
            Special Guide Quest Attributes
          </span>
          <span className="h-px flex-1 bg-gray-300 dark:bg-gray-700" />
        </div>

        <div>
          <Alert variant={AlertVariant.INFO}>
            <p className="my-2">
              Quests can become special when you start to create chains of
              quests that should only appear during special events or when a
              specific condition or set of is matched.
            </p>
            <p className="my-2">
              These quests will jump in and replace what ever would be the
              players current or next guide quest with this chain of quests.
            </p>
            <p className="my-2">
              For example if at level 10 you want to teach crafting, and you
              also want to do a special crafting quest for when level 10 and a
              specific event is going on you can set up the latter to jump in
              and replace the players guide quest with that one instead.
            </p>
          </Alert>
        </div>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Only During Event
        </label>
        <Dropdown
          items={eventItems}
          pre_selected_item={preSelectedEvent}
          on_select={(value) =>
            handleUpdateFormData('only_during_event', value)
          }
        />
      </div>

      {renderParentGuideQuestOption()}

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Only When the character reaches level
        </label>
        <Input
          default_value={getDefaultUnlockLevel()}
          on_change={(value) => handleUpdateFormData('unlock_at_level', value)}
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsBasicQuestAttributes;
