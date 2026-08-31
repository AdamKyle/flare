import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseNpcFormOptionsDefinition from './definitions/use-npc-form-options-definition';
import NpcFormOptionsDefinition from '../definitions/npc-form-options-definition';
import { NpcApiMessages } from '../enums/npc-api-messages';
import { NpcApiUrls } from '../enums/npc-api-urls';

export const useNpcFormOptions = (
  gameMapId: number
): UseNpcFormOptionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [formOptions, setFormOptions] =
    useState<NpcFormOptionsDefinition | null>(null);
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

    const fetchFormOptions = async () => {
      try {
        const result = await apiHandler.get<
          NpcFormOptionsDefinition,
          Record<string, never>
        >(getUrl(NpcApiUrls.OPTIONS, { gameMap: gameMapId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setFormOptions(result);
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

        setError({ message: NpcApiMessages.LoadFormOptions });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchFormOptions();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, gameMapId]);

  return { form_options: formOptions, loading, error };
};
