import { range, zipObject } from 'lodash';
import React, { useMemo } from 'react';

import ManageGuideQuestStepProps from './types/manage-guide-quest-step-props';
import { useManageFormSectionData } from '../hooks/use-manage-form-section-data';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredLevels = ({
  data_for_component,
  on_update,
}: ManageGuideQuestStepProps) => {
  const { convertObjectToKeyValue, handleUpdateFormData } =
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

  const skillItems = useMemo(() => {
    return convertObjectToKeyValue(data_for_component.game_skills);
  }, [convertObjectToKeyValue, data_for_component.game_skills]);

  const skillTypeItems = useMemo(() => {
    const map = zipObject(
      range(data_for_component.skill_types.length),
      data_for_component.skill_types
    );
    return convertObjectToKeyValue(map);
  }, [convertObjectToKeyValue, data_for_component.skill_types]);

  const getPreselected = (
    items: DropdownItem[],
    candidate: number | null | undefined
  ): DropdownItem | undefined => {
    if (candidate === null || candidate === undefined) {
      return undefined;
    }

    const valueAsString = String(candidate);

    const found = items.find((item) => item.value === valueAsString);

    if (!found) {
      return undefined;
    }

    return found;
  };

  const preselectedRequiredSkill = useMemo(() => {
    return getPreselected(skillItems, guideQuest?.required_skill);
  }, [skillItems, guideQuest?.required_skill]);

  const preselectedSecondarySkill = useMemo(() => {
    return getPreselected(skillItems, guideQuest?.required_secondary_skill);
  }, [skillItems, guideQuest?.required_secondary_skill]);

  const preselectedSkillType = useMemo(() => {
    return getPreselected(skillTypeItems, guideQuest?.required_skill_type);
  }, [skillTypeItems, guideQuest?.required_skill_type]);

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Character Level
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_level)}
          on_change={(value) => handleUpdateFormData('required_level', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill
        </label>
        <Dropdown
          items={skillItems}
          pre_selected_item={preselectedRequiredSkill}
          on_select={(value) => handleUpdateFormData('required_skill', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Level
        </label>
        <Input
          default_value={getDefaultString(guideQuest?.required_skill_level)}
          on_change={(value) =>
            handleUpdateFormData('required_skill_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Secondary Required Skill
        </label>
        <Dropdown
          items={skillItems}
          pre_selected_item={preselectedSecondarySkill}
          on_select={(value) =>
            handleUpdateFormData('required_secondary_skill', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Secondary Required Skill Level
        </label>
        <Input
          default_value={getDefaultString(
            guideQuest?.required_secondary_skill_level
          )}
          on_change={(value) =>
            handleUpdateFormData('required_secondary_skill_level', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Type
        </label>
        <Dropdown
          items={skillTypeItems}
          pre_selected_item={preselectedSkillType}
          on_select={(value) =>
            handleUpdateFormData('required_skill_type', value)
          }
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Type Level
        </label>
        <Input
          default_value={getDefaultString(
            guideQuest?.required_skill_type_level
          )}
          on_change={(value) =>
            handleUpdateFormData('required_skill_type_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredLevels;
