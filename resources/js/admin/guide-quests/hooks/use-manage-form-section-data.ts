import { isNil } from 'lodash';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseManageFormSectionDefinition from './definitions/use-manage-form-section-definition';
import UseManagementFormSectionParams from './definitions/use-manage-form-section-params';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const useManageFormSectionData = ({
  on_update,
  initial_values,
}: UseManagementFormSectionParams): UseManageFormSectionDefinition => {
  const debounceTimersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(
    new Map()
  );

  const [_, setStepFormData] = useState<Partial<GuideQuestDefinition> | null>(
    null
  );

  useEffect(() => {
    if (initial_values) {
      setStepFormData((previous) => {
        const next = { ...(previous ?? {}), ...initial_values };

        on_update(next);

        return next;
      });
    }

    const timers = debounceTimersRef.current;

    return () => {
      timers.forEach((timeoutId) => clearTimeout(timeoutId));
      timers.clear();
    };
  }, [initial_values, on_update]);

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
        setStepFormData((previous) => {
          const next = { ...(previous ?? {}), [key]: value.value };
          on_update(next);
          return next;
        });
        return;
      }

      const timers = debounceTimersRef.current;
      const existing = timers.get(key);

      if (existing) {
        clearTimeout(existing);
      }

      const timeoutId = setTimeout(() => {
        setStepFormData((previous) => {
          const next = { ...(previous ?? {}), [key]: value };
          on_update(next);
          return next;
        });
        timers.delete(key);
      }, 300);

      timers.set(key, timeoutId);
    },
    [on_update]
  );

  const convertArrayToDropDown = (data: string[]): DropdownItem[] => {
    return data.map((label: string, index: number) => {
      return {
        label,
        value: index,
      };
    });
  };

  const getPreSelected = useCallback(
    (
      items: DropdownItem[],
      candidate: string | number | null
    ): DropdownItem | undefined => {
      if (!items) {
        return;
      }

      if (items.length === 0) {
        return;
      }

      if (isNil(candidate)) {
        return;
      }

      const found = items.find(
        (item) => String(item.value) === String(candidate)
      );

      if (!found) {
        return;
      }

      return found;
    },
    []
  );

  return {
    convertObjectToKeyValue,
    handleUpdateFormData,
    convertArrayToDropDown,
    getPreSelected,
  };
};
