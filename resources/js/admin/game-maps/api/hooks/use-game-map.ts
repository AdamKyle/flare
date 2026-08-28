import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseGameMapDefinition from './definitions/use-game-map-definition';
import { GameMapDetailDefinition } from '../definitions/game-map-editor-definition';
import { GameMapApiMessages } from '../enums/game-map-api-messages';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

export const useGameMap = (gameMapId: number): UseGameMapDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [gameMap, setGameMap] = useState<GameMapDetailDefinition | null>(null);
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
        GameMapDetailDefinition,
        Record<string, never>
      >(getUrl(GameMapApiUrls.SHOW, { gameMap: gameMapId }), {
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

      setError({ message: GameMapApiMessages.Load });
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
