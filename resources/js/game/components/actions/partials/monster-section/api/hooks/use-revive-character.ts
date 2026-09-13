import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { BattleApiUrls } from '../enums/battle-api-urls';
import UseReviveCharacterDefinition from './definitions/use-revive-character-definition';

/**
 * Call the existing backend Character revive endpoint. The resulting
 * `is_dead`/health state update arrives through the global Character
 * websocket wire, not from this response.
 */
export const useReviveCharacter = (
  characterId: number
): UseReviveCharacterDefinition => {
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

  const revive = useCallback(async (): Promise<boolean> => {
    if (isSubmittingRef.current) {
      return false;
    }

    isSubmittingRef.current = true;
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      await apiHandler.post<
        { message: string },
        AxiosRequestConfig<{ message: string }>,
        Record<string, never>
      >(
        getUrl(BattleApiUrls.REVIVE, { character: characterId }),
        {},
        { signal: controller.signal }
      );

      return true;
    } catch (requestError) {
      if (axios.isCancel(requestError)) {
        return false;
      }

      setError('Unable to revive your Character.');

      return false;
    } finally {
      isSubmittingRef.current = false;
      abortControllerRef.current = null;
      setLoading(false);
    }
  }, [apiHandler, getUrl, characterId]);

  return { loading, error, revive };
};
