import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseGameMapForEditDefinition from './definitions/use-game-map-for-edit-definition';
import GameMapFormResponseDefinition from '../../definitions/game-map-form-response-definition';
import { GameMapApiMessages } from '../enums/game-map-api-messages';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

export const useGameMapForEdit = (
  gameMapId: number | null
): UseGameMapForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [gameMap, setGameMap] = useState<GameMapFormResponseDefinition | null>(
    null
  );
  const [loading, setLoading] = useState(gameMapId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (gameMapId === null) {
      setLoading(false);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchGameMap = async () => {
      try {
        const result = await apiHandler.get<
          GameMapFormResponseDefinition,
          Record<string, never>
        >(getUrl(GameMapApiUrls.EDIT, { gameMap: gameMapId }), {
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
    };

    void fetchGameMap();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, gameMapId]);

  return { game_map: gameMap, loading, error };
};
