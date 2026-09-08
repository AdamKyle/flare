import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UsePlayerGameMapDetailDefinition from './definitions/use-player-game-map-detail-definition';
import GameMapFactualDefinition from '../../types/game-map-factual-definition';
import { PlayerGameMapDetailApiUrls } from '../enums/player-game-map-detail-api-urls';

export const usePlayerGameMapDetail = (
  gameMapId: number
): UsePlayerGameMapDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [gameMap, setGameMap] = useState<GameMapFactualDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchGameMap = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        GameMapFactualDefinition,
        Record<string, never>
      >(getUrl(PlayerGameMapDetailApiUrls.DETAIL, { gameMap: gameMapId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setGameMap(result);
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

      setError({ message: 'Unable to load this Game Map.' });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, gameMapId]);

  useEffect(() => {
    void fetchGameMap();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchGameMap, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return {
    game_map: gameMap,
    loading,
    error,
    refresh,
  };
};
