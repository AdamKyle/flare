import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseClassDetailDefinition from './definitions/use-class-detail-definition';
import ClassDetailDefinition from '../definitions/class-detail-definition';
import { ClassApiMessages } from '../enums/class-api-messages';
import { ClassApiUrls } from '../enums/class-api-urls';

export const useClassDetail = (classId: number): UseClassDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [gameClass, setGameClass] = useState<ClassDetailDefinition | null>(
    null
  );
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

    const fetchClass = async () => {
      try {
        const result = await apiHandler.get<
          ClassDetailDefinition,
          Record<string, never>
        >(getUrl(ClassApiUrls.SHOW, { gameClass: classId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setGameClass(result);
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

        setError({ message: ClassApiMessages.Load });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchClass();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, classId]);

  return { game_class: gameClass, loading, error };
};
