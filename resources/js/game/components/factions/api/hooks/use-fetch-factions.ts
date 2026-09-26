import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseFetchFactionsDefinition from './definitions/use-fetch-factions-definition';
import FactionDefinition from '../definitions/faction-definition';
import FactionsResponseDefinition from '../definitions/factions-response-definition';
import { FactionsApiUrls } from '../enums/factions-api-urls';

export const useFetchFactions = (
  characterId: number
): UseFetchFactionsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [factions, setFactions] = useState<FactionDefinition[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchFactions = useCallback(async (): Promise<void> => {
    if (characterId <= 0) {
      return;
    }

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<FactionsResponseDefinition, never>(
        getUrl(FactionsApiUrls.FACTIONS, { character: characterId }),
        { signal: controller.signal }
      );

      if (abortControllerRef.current !== controller) {
        return;
      }

      setFactions(result.factions);
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return;
      }

      if (abortControllerRef.current !== controller) {
        return;
      }

      setError(
        resolveApiErrorMessage(requestError, 'Unable to load your Factions.')
      );
    } finally {
      if (abortControllerRef.current === controller) {
        abortControllerRef.current = null;
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, characterId]);

  useEffect(() => {
    void fetchFactions();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchFactions]);

  return { factions, loading, error };
};
