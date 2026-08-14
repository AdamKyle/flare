import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import CelestialStatsResponseDefinition from '../definitions/celestial-stats-response-definition';
import { ConjureApiUrls } from '../enums/conjure-api-urls';
import UseFetchCelestialStatsApiDefinition from './definitions/use-fetch-celestial-stats-api-definition';
import UseFetchCelestialStatsApiParams from './definitions/use-fetch-celestial-stats-api-params';

const UNABLE_TO_LOAD_CELESTIAL_DETAILS_MESSAGE =
  'Unable to load celestial details.';

export const useFetchCelestialStatsApi = (
  params: UseFetchCelestialStatsApiParams
): UseFetchCelestialStatsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] = useState<CelestialStatsResponseDefinition | null>(
    null
  );
  const [error, setError] =
    useState<UseFetchCelestialStatsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);

  const isMountedRef = useRef(true);
  const requestIdRef = useRef(0);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  const fetchCelestialStats = useCallback(
    async (monsterId: number) => {
      const requestId = ++requestIdRef.current;

      setLoading(true);
      setError(null);
      setData(null);

      const url = getUrl(ConjureApiUrls.CELESTIAL_STATS, {
        character: params.character_id,
        monsterId,
      });

      try {
        const result = await apiHandler.get<
          CelestialStatsResponseDefinition,
          never
        >(url);

        if (!isMountedRef.current || requestId !== requestIdRef.current) {
          return;
        }

        setData(result);
      } catch (err) {
        if (!isMountedRef.current || requestId !== requestIdRef.current) {
          return;
        }

        if (!(err instanceof AxiosError)) {
          setError({ message: UNABLE_TO_LOAD_CELESTIAL_DETAILS_MESSAGE });
          return;
        }

        if (err.response?.status === 401) {
          handleInactivity({ setError, response: err });
          return;
        }

        setError(
          err.response?.data ?? {
            message: UNABLE_TO_LOAD_CELESTIAL_DETAILS_MESSAGE,
          }
        );
      } finally {
        if (isMountedRef.current && requestId === requestIdRef.current) {
          setLoading(false);
        }
      }
    },
    [apiHandler, getUrl, params.character_id, handleInactivity]
  );

  return {
    data,
    loading,
    error,
    fetchCelestialStats,
  };
};
