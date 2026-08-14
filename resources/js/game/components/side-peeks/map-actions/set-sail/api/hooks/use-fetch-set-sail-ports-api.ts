import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import FetchSetSailPortsResponseDefinition from '../definitions/fetch-set-sail-ports-response-definition';
import { SetSailApiUrls } from '../enums/set-sail-api-urls';
import UseFetchSetSailPortsApiDefinition from './definitions/use-fetch-set-sail-ports-api-definition';
import UseFetchSetSailPortsApiParams from './definitions/use-fetch-set-sail-ports-api-params';

const UNABLE_TO_LOAD_PORTS_MESSAGE = 'Unable to load Set Sail ports.';

export const useFetchSetSailPortsApi = (
  params: UseFetchSetSailPortsApiParams
): UseFetchSetSailPortsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] = useState<FetchSetSailPortsResponseDefinition | null>(
    null
  );
  const [error, setError] =
    useState<UseFetchSetSailPortsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  const url = getUrl(SetSailApiUrls.SET_SAIL_PORTS, {
    character: params.character_id,
  });

  const fetchSetSailPorts = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        FetchSetSailPortsResponseDefinition,
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
        setError({ message: UNABLE_TO_LOAD_PORTS_MESSAGE });
        return;
      }

      if (err.response?.status === 401) {
        handleInactivity({ setError, response: err });
        return;
      }

      setError(err.response?.data ?? { message: UNABLE_TO_LOAD_PORTS_MESSAGE });
    } finally {
      if (isMountedRef.current) {
        setLoading(false);
      }
    }
  }, [apiHandler, url, handleInactivity]);

  useEffect(() => {
    void fetchSetSailPorts();
  }, [fetchSetSailPorts]);

  return {
    data,
    loading,
    error,
  };
};
