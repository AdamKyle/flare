import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseImportGameMapsDefinition from './definitions/use-import-game-maps-definition';
import GameMapImportResponseDefinition from '../definitions/game-map-import-response-definition';
import { GameMapApiMessages } from '../enums/game-map-api-messages';
import { GameMapApiUrls } from '../enums/game-map-api-urls';

export const useImportGameMaps = (): UseImportGameMapsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const [importing, setImporting] = useState(false);
  const [error, setError] =
    useState<UseImportGameMapsDefinition['error']>(null);
  const importingRef = useRef(false);
  const isMountedRef = useRef(true);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
      abortControllerRef.current?.abort();
    };
  }, []);

  const importGameMaps = async (file: File): Promise<boolean> => {
    if (importingRef.current) {
      return false;
    }

    importingRef.current = true;
    setImporting(true);
    setError(null);

    const formData = new FormData();
    const controller = new AbortController();

    formData.append('game_maps_import', file);
    abortControllerRef.current = controller;

    try {
      await apiHandler.post<
        GameMapImportResponseDefinition,
        Record<string, never>,
        FormData
      >(getUrl(GameMapApiUrls.IMPORT), formData, {
        signal: controller.signal,
      });

      return true;
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance) || !isMountedRef.current) {
        return false;
      }

      if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
        if (errorInstance.response?.status === 401) {
          handleInactivity({ setError, response: errorInstance });

          return false;
        }

        setError({
          message:
            errorInstance.response?.data?.message ??
            GameMapApiMessages.ImportFallback,
        });

        return false;
      }

      setError({ message: GameMapApiMessages.ImportFallback });

      return false;
    } finally {
      importingRef.current = false;

      if (isMountedRef.current) {
        abortControllerRef.current = null;
        setImporting(false);
      }
    }
  };

  return {
    import_game_maps: importGameMaps,
    importing,
    error,
  };
};
