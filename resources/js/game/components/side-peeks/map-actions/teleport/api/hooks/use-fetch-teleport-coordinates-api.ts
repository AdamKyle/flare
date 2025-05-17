import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import TeleportCoordinatesApiDefinition from './definitions/teleport-coordinates-api-definition';
import UseFetchTeleportCoordinatesAoiParams from './definitions/use-fetch-teleport-coordinates-aoi-params';
import UseFetchTeleportCoordinatesApiDefinition from './definitions/use-fetch-teleport-coordinates-api-definition';

export const useFetchTeleportCoordinatesApi = (
  params: UseFetchTeleportCoordinatesAoiParams
): UseFetchTeleportCoordinatesApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<TeleportCoordinatesApiDefinition | null>(
    null
  );
  const [error, setError] =
    useState<UseFetchTeleportCoordinatesApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  let url = getUrl(params.url, { character: params.character_id });

  const fetchTeleportCoordinates = useCallback(
    async () => {
      if (params.character_id <= 0) {
        setLoading(false);
        return;
      }

      try {
        const result = await apiHandler.get<
          TeleportCoordinatesApiDefinition,
          AxiosRequestConfig<AxiosResponse<TeleportCoordinatesApiDefinition>>
        >(url);

        setData(result);
      } catch (err) {
        if (err instanceof AxiosError) {
          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url]
  );

  useEffect(() => {
    fetchTeleportCoordinates().catch(() => {});
  }, [fetchTeleportCoordinates]);

  return {
    data,
    error,
    loading,
  };
};
