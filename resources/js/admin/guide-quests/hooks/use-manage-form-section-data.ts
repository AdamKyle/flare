import { useEffect, useMemo, useState } from 'react';
import { debounce } from 'lodash';

import UseManagementFormSectionParams from './definitions/use-manage-form-section-params';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';
import UseManageFormSectionDefinition from './definitions/use-manage-form-section-definition';

export const useManageFormSectionData = ({ on_update }: UseManagementFormSectionParams): UseManageFormSectionDefinition => {
  const [requiredQuestLevels, setRequiredQuestLevels] = useState<Partial<GuideQuestDefinition> | null>(null);

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

  const convertObjectToKeyValue = (object: { [key: string | number]: string }) => {
    return Object.entries(object)
      .map(([id, label]) => ({ value: Number(id), label }))
      .sort((a, b) => a.value - b.value);
  };

  const handleUpdateFormData = (key: string, value: DropdownItem | string) => {
    setRequiredQuestLevels((prev) => {
      const keyValue = typeof value === 'object' ? value.value : value;

      return {
        ...prev,
        [key]: keyValue,
      };
    });
  };

  const convertArrayToDropDown = (data: string[]): DropdownItem[] => {
    return data.map((skillType: string) => {
      return {
        label: skillType,
        value: skillType,
      };
    });
  }

  return {
    convertObjectToKeyValue,
    handleUpdateFormData,
    convertArrayToDropDown,
  };
};
