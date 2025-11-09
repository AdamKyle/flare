import { useCallback, useEffect, useRef, useState } from 'react';

import UseManageFormSectionDefinition from './definitions/use-manage-form-section-definition';
import UseManagementFormSectionParams from './definitions/use-manage-form-section-params';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const useManageFormSectionData = ({
  on_update,
}: UseManagementFormSectionParams): UseManageFormSectionDefinition => {
  const debounceTimersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(
    new Map()
  );

  const [stepFormData, setSetpFormData] =
    useState<Partial<GuideQuestDefinition> | null>(null);

  useEffect(() => {
    if (!stepFormData) {
      return;
    }

    on_update(stepFormData);
  }, [stepFormData, on_update]);

  useEffect(() => {
    return () => {
      debounceTimersRef.current.forEach((timeoutId) => clearTimeout(timeoutId));
      debounceTimersRef.current.clear();
    };
  }, []);

  const convertObjectToKeyValue = (object: {
    [key: string | number]: string;
  }) => {
    return Object.entries(object).map(([valueAsString, label]) => ({
      value: valueAsString,
      label,
    }));
  };

  const handleUpdateFormData = useCallback(
    (key: string, value: DropdownItem | string) => {
      if (typeof value === 'object') {
        setSetpFormData((previous) => ({ ...previous, [key]: value.value }));
        return;
      }

      const timers = debounceTimersRef.current;
      const existing = timers.get(key);

      if (existing) {
        clearTimeout(existing);
      }

      const timeoutId = setTimeout(() => {
        setSetpFormData((previous) => ({ ...previous, [key]: value }));
        timers.delete(key);
      }, 300);

      timers.set(key, timeoutId);
    },
    []
  );

  const convertArrayToDropDown = (data: string[]): DropdownItem[] => {
    return data.map((skillType: string) => {
      return {
        label: skillType,
        value: skillType,
      };
    });
  };

  return {
    convertObjectToKeyValue,
    handleUpdateFormData,
    convertArrayToDropDown,
  };
};
