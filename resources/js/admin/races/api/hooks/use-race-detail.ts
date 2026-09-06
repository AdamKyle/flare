import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseRaceDetailDefinition from './definitions/use-race-detail-definition';
import RaceDefinition from '../definitions/race-definition';
import { RaceApiMessages } from '../enums/race-api-messages';
import { RaceApiUrls } from '../enums/race-api-urls';

export const useRaceDetail = (raceId: number): UseRaceDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [race, setRace] = useState<RaceDefinition | null>(null);
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

    const fetchRace = async () => {
      try {
        const result = await apiHandler.get<
          RaceDefinition,
          Record<string, never>
        >(getUrl(RaceApiUrls.SHOW, { gameRace: raceId }), {
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

        setError({ message: RaceApiMessages.Load });
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
