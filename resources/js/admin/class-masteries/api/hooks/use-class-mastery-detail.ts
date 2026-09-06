import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseClassMasteryDetailDefinition from './definitions/use-class-mastery-detail-definition';
import ClassMasteryDetailDefinition from '../definitions/class-mastery-detail-definition';
import { ClassMasteryApiMessages } from '../enums/class-mastery-api-messages';
import { ClassMasteryApiUrls } from '../enums/class-mastery-api-urls';

export const useClassMasteryDetail = (
  classMasteryId: number
): UseClassMasteryDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [classMastery, setClassMastery] =
    useState<ClassMasteryDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
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
          ClassMasteryDetailDefinition,
          Record<string, never>
        >(
          getUrl(ClassMasteryApiUrls.SHOW, {
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
