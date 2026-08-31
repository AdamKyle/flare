import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseQuestFormOptionsDefinition from './definitions/use-quest-form-options-definition';
import QuestFormOptionsDefinition from '../definitions/quest-form-options-definition';
import { QuestApiMessages } from '../enums/quest-api-messages';
import { QuestApiUrls } from '../enums/quest-api-urls';

export const useQuestFormOptions = (): UseQuestFormOptionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [formOptions, setFormOptions] =
    useState<QuestFormOptionsDefinition | null>(null);
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
          QuestFormOptionsDefinition,
          Record<string, never>
        >(getUrl(QuestApiUrls.OPTIONS), { signal: controller.signal });

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

        setError({ message: QuestApiMessages.LoadFormOptions });
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
  }, [apiHandler, getUrl]);

  return { form_options: formOptions, loading, error };
};
