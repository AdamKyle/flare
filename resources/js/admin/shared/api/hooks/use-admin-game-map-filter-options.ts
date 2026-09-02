import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseAdminGameMapFilterOptionsDefinition from './definitions/use-admin-game-map-filter-options-definition';
import QuestBrowseOptionsDefinition from '../../../../game/reusable-components/quest/api/definitions/quest-browse-options-definition';
import { AdminSharedApiUrls } from '../enums/admin-shared-api-urls';

/**
 * Load the factual default Game Map and ordered Game Map list shared across
 * every Admin list's Game Map filter (NPCs, Locations, Monsters). Reuses
 * the existing Quest browse-options endpoint and response shape — no new
 * backend route, and no dependency from these feature modules into Admin
 * Quest UI (only the response type is shared).
 */
export const useAdminGameMapFilterOptions =
  (): UseAdminGameMapFilterOptionsDefinition => {
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
          >(getUrl(AdminSharedApiUrls.GAME_MAP_FILTER_OPTIONS), {
            signal: controller.signal,
          });

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

          setError({ message: 'Unable to load Game Map filter options.' });
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
