import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseGameMapEditorDefinition from './definitions/use-game-map-editor-definition';
import GameMapEditorDefinition from '../definitions/game-map-editor-definition';
import { GameMapApiMessages } from '../enums/game-map-api-messages';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

export const useGameMapEditor = (
  gameMapId: number
): UseGameMapEditorDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [editor, setEditor] = useState<GameMapEditorDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchEditor = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        GameMapEditorDefinition,
        Record<string, never>
      >(getUrl(GameMapApiUrls.EDITOR, { gameMap: gameMapId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setEditor(result);
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

      setError({ message: GameMapApiMessages.LoadEditor });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, gameMapId]);

  useEffect(() => {
    void fetchEditor();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchEditor, refreshToken]);

  const refresh = useCallback((): void => {
    setRefreshToken((value) => value + 1);
  }, []);

  return {
    editor,
    loading,
    error,
    refresh,
  };
};
