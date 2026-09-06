import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseClassMasteryForEditDefinition from './definitions/use-class-mastery-for-edit-definition';
import ClassMasteryFormDefinition from '../definitions/class-mastery-form-definition';
import { ClassMasteryApiMessages } from '../enums/class-mastery-api-messages';
import { ClassMasteryApiUrls } from '../enums/class-mastery-api-urls';

export const useClassMasteryForEdit = (
  classMasteryId: number | null
): UseClassMasteryForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [classMastery, setClassMastery] =
    useState<ClassMasteryFormDefinition | null>(null);
  const [loading, setLoading] = useState(classMasteryId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (classMasteryId === null) {
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

    const fetchClassMastery = async () => {
      try {
        const result = await apiHandler.get<
          ClassMasteryFormDefinition,
          Record<string, never>
        >(
          getUrl(ClassMasteryApiUrls.EDIT, {
            gameClassSpecial: classMasteryId,
          }),
          {
            signal: controller.signal,
          }
        );

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setClassMastery(result);
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

        setError({ message: ClassMasteryApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchClassMastery();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, classMasteryId]);

  return { class_mastery: classMastery, loading, error };
};
