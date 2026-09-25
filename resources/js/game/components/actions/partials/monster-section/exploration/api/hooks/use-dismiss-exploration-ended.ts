import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseDismissExplorationDefinition from './definitions/use-dismiss-exploration-definition';
import { ExplorationApiUrls } from '../../../api/enums/exploration-api-urls';
import ExplorationOutputResponseDefinition from '../../types/exploration-output-response-definition';

export const useDismissExplorationEnded = (
  characterId: number
): UseDismissExplorationDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);
  const isSubmittingRef = useRef(false);

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);

  const dismiss =
    useCallback(async (): Promise<ExplorationOutputResponseDefinition | null> => {
      if (isSubmittingRef.current || characterId <= 0) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          ExplorationOutputResponseDefinition,
          AxiosRequestConfig<ExplorationOutputResponseDefinition>,
          Record<string, never>
        >(
          getUrl(ExplorationApiUrls.DISMISS_ENDED_EXPLORATION, {
            character: characterId,
          }),
          {},
          { signal: controller.signal }
        );

        return result;
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError(
          'Unable to dismiss the ended Exploration summary. Please try again.'
        );

        return null;
      } finally {
        isSubmittingRef.current = false;
        abortControllerRef.current = null;
        setLoading(false);
      }
    }, [apiHandler, getUrl, characterId]);

  return { loading, error, dismiss };
};
