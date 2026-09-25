import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseSlotsDefinition from './definitions/use-slots-definition';
import SlotsResponseDefinition from '../definitions/slots-response-definition';
import { SlotsApiUrls } from '../enums/slots-api-urls';

export const useSlots = (): UseSlotsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [data, setData] = useState<UseSlotsDefinition['data']>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<UseSlotsDefinition['error']>(null);
  const [refreshCount, setRefreshCount] = useState(0);

  const abortControllerRef = useRef<AbortController | null>(null);

  const refetch = useCallback(() => {
    setRefreshCount((previousCount) => previousCount + 1);
  }, []);

  useEffect(() => {
    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchSlots = async () => {
      try {
        const result = await apiHandler.get<SlotsResponseDefinition, never>(
          getUrl(SlotsApiUrls.GET_SLOTS),
          { signal: controller.signal }
        );

        setData(result);
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return;
        }

        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to load the slot machine.'
          ),
        });

        if (requestError instanceof AxiosError) {
          handleInactivity({ response: requestError, setError });
        }
      } finally {
        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    };

    void fetchSlots();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, handleInactivity, refreshCount]);

  return { data, loading, error, refetch };
};
