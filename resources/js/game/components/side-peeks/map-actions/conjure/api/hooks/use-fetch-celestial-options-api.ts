import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { ConjureApiUrls } from '../enums/conjure-api-urls';
import FetchCelestialOptionsResponseDefinition from '../definitions/fetch-celestial-options-response-definition';
import UseFetchCelestialOptionsApiDefinition from './definitions/use-fetch-celestial-options-api-definition';
import UseFetchCelestialOptionsApiParams from './definitions/use-fetch-celestial-options-api-params';

const UNABLE_TO_LOAD_CELESTIALS_MESSAGE = 'Unable to load conjurable celestials.';

export const useFetchCelestialOptionsApi = (
  params: UseFetchCelestialOptionsApiParams
): UseFetchCelestialOptionsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] =
    useState<FetchCelestialOptionsResponseDefinition | null>(null);
  const [error, setError] =
    useState<UseFetchCelestialOptionsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  const url = getUrl(ConjureApiUrls.CELESTIAL_OPTIONS, {
    character: params.character_id,
  });

  const fetchCelestialOptions = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        FetchCelestialOptionsResponseDefinition,
        never
      >(url);

      if (!isMountedRef.current) {
        return;
      }

      setData(result);
    } catch (err) {
      if (!isMountedRef.current) {
        return;
      }

      if (!(err instanceof AxiosError)) {
        setError({ message: UNABLE_TO_LOAD_CELESTIALS_MESSAGE });
        return;
      }

      if (err.response?.status === 401) {
        handleInactivity({ setError, response: err });
        return;
      }

      setError(
        err.response?.data ?? { message: UNABLE_TO_LOAD_CELESTIALS_MESSAGE }
      );
    } finally {
      if (isMountedRef.current) {
        setLoading(false);
      }
    }
  }, [apiHandler, url, handleInactivity]);

  useEffect(() => {
    void fetchCelestialOptions();
  }, [fetchCelestialOptions]);

  return {
    data,
    loading,
    error,
  };
};
