import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseQuestBrowseOptionsDefinition from './definitions/use-quest-browse-options-definition';
import QuestBrowseOptionsDefinition from '../../../../game/reusable-components/quest/api/definitions/quest-browse-options-definition';
import { QuestApiMessages } from '../enums/quest-api-messages';
import { QuestApiUrls } from '../enums/quest-api-urls';

export const useQuestBrowseOptions = (): UseQuestBrowseOptionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [options, setOptions] = useState<QuestBrowseOptionsDefinition | null>(
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

    const fetchOptions = async () => {
      try {
        const result = await apiHandler.get<
          QuestBrowseOptionsDefinition,
          Record<string, never>
        >(getUrl(QuestApiUrls.BROWSE_OPTIONS), { signal: controller.signal });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setOptions(result);
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

        setError({ message: QuestApiMessages.LoadBrowseOptions });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchOptions();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl]);

  return { options, loading, error };
};
