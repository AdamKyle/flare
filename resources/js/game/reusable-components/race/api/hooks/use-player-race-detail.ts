import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UsePlayerRaceDetailDefinition from './definitions/use-player-race-detail-definition';
import RaceDetailDefinition from '../../types/race-detail-definition';
import { PlayerRaceDetailApiUrls } from '../enums/player-race-detail-api-urls';

export const usePlayerRaceDetail = (
  raceId: number
): UsePlayerRaceDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [race, setRace] = useState<RaceDetailDefinition | null>(null);
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

    const fetchRace = async (): Promise<void> => {
      try {
        const result = await apiHandler.get<
          RaceDetailDefinition,
          Record<string, never>
        >(getUrl(PlayerRaceDetailApiUrls.DETAIL, { race: raceId }), {
          signal: controller.signal,
        });

        if (requestGenerationRef.current !== requestGeneration) {
          return;
        }

        setRace(result);
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

        setError({ message: 'Unable to load this Race.' });
      } finally {
        if (requestGenerationRef.current === requestGeneration) {
          setLoading(false);
        }
      }
    };

    void fetchRace();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, raceId]);

  return { race, loading, error };
};
