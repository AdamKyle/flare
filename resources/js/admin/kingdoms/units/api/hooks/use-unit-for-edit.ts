import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseUnitForEditDefinition from './definitions/use-unit-for-edit-definition';
import UnitFormDefinition from '../definitions/unit-form-definition';
import { UnitApiMessages } from '../enums/unit-api-messages';
import { UnitApiUrls } from '../enums/unit-api-urls';

export const useUnitForEdit = (
  unitId: number | null
): UseUnitForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [unit, setUnit] = useState<UnitFormDefinition | null>(null);
  const [loading, setLoading] = useState(unitId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (unitId === null) {
      setLoading(false);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchUnit = async () => {
      try {
        const result = await apiHandler.get<
          UnitFormDefinition,
          Record<string, never>
        >(getUrl(UnitApiUrls.EDIT, { gameUnit: unitId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setUnit(result);
      } catch (errorInstance) {
        if (axios.isCancel(errorInstance)) {
          return;
        }

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
          setError({
            message:
              errorInstance.response?.data?.message ?? errorInstance.message,
          });

          return;
        }

        setError({ message: UnitApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchUnit();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, unitId]);

  return { unit, loading, error };
};
