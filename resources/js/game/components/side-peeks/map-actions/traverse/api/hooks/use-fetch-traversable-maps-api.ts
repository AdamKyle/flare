import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { TraversalApiUrls } from '../enums/traversal-api-urls';
import UseFetchTraversableMapsDefinition from './deffinitions/use-fetch-traversable-maps-definition';
import UseFetchTraversableMapsResponse from './deffinitions/use-fetch-traversable-maps-response';

export const useOpenTraverseModalApi =
  (): UseFetchTraversableMapsDefinition => {
    const { apiHandler, getUrl } = useApiHandler();

    const [data, setData] = useState<UseFetchTraversableMapsResponse[] | null>(
      null
    );
    const [error, setError] =
      useState<UseFetchTraversableMapsDefinition['error']>(null);
    const [loading, setLoading] = useState(true);

    const url = getUrl(TraversalApiUrls.TRAVERSABLE_MAPS);

    const teleportPlayer = useCallback(async () => {
      try {
        const result = await apiHandler.get<
          UseFetchTraversableMapsResponse[],
          AxiosRequestConfig<UseFetchTraversableMapsResponse[]>
        >(url);

        setData(result);
      } catch (error) {
        if (error instanceof AxiosError) {
          setError(error.response?.data);
        }
      } finally {
        setLoading(false);
      }
    }, [apiHandler, url]);

    useEffect(() => {
      teleportPlayer().catch(() => {});
    }, [teleportPlayer, url]);

    return {
      data,
      loading,
      error,
    };
  };
