import { debounce } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';

import ManageGuideQuesRequired from './types/manage-guide-quests-required-levels-props';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';

const ManageGuideQuestsRequiredLevels = ({
  data_for_component,
  on_update,
}: ManageGuideQuesRequired) => {
  const [requiredQuestLevels, setRequiredQuestLevels] =
    useState<Partial<GuideQuestDefinition> | null>(null);

  const convertObjectToKeyValue = (object: {
    [key: string | number]: string;
  }) => {
    return Object.entries(object)
      .map(([id, label]) => ({ value: Number(id), label }))
      .sort((a, b) => a.value - b.value);
  };

  const requiredSkillsOptions = useMemo<DropdownItem[]>(() => {
    const gameSkills = data_for_component.game_skills;

    return convertObjectToKeyValue(gameSkills);
  }, [data_for_component.game_skills]);

  const requiredSkillTypeOptions = useMemo<DropdownItem[]>(() => {
    return data_for_component.skill_types.map((skillType: string) => {
      return {
        label: skillType,
        value: skillType,
      };
    });
  }, [data_for_component.skill_types]);

  const emitUp = useMemo(
    () =>
      debounce((payload: Partial<GuideQuestDefinition>) => {
        on_update(payload);
      }, 300),
    [on_update]
  );

  useEffect(() => {
    if (!requiredQuestLevels) {
      return;
    }

    emitUp(requiredQuestLevels);

    return () => {
      emitUp.cancel();
    };
  }, [requiredQuestLevels, emitUp]);

  const handleUpdateFormData = (key: string, value: DropdownItem | string) => {
    setRequiredQuestLevels((prev) => {
      const keyValue = typeof value === 'object' ? value.value : value;

      return {
        ...prev,
        [key]: keyValue,
      };
    });
  };

  return (
    <div className="space-y-4">
      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Level
        </label>
        <Input
          on_change={(value) => handleUpdateFormData('required_level', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill
        </label>
        <Dropdown
          items={requiredSkillsOptions}
          on_select={(value) => handleUpdateFormData('required_skill', value)}
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
          Required Skill Level
        </label>
        <Input
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
          items={requiredSkillsOptions}
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
          items={requiredSkillTypeOptions}
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
          on_change={(value) =>
            handleUpdateFormData('required_skill_type_level', value)
          }
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestsRequiredLevels;
