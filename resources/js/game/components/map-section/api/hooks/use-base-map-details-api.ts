import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import BaseMapApiDefinition from './definitions/base-map-api-definition';
import BaseMapDetailsApiDefinition from './definitions/base-map-details-api-definition';
import MapDetailsApiRequestParams from './definitions/map-details-api-request-params';

const useBaseMapDetailsApi = (
  params: MapDetailsApiRequestParams
): BaseMapDetailsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<BaseMapApiDefinition | null>(null);
  const [error, setError] =
    useState<BaseMapDetailsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  let url = '';

  if (params.characterData) {
    url = getUrl(params.url, { character: params.characterData.id });
  }

  const fetchCharacterMapDetails = useCallback(
    async () => {
      if (!params.characterData) {
        setLoading(false);
        return;
      }

      try {
        const result = await apiHandler.get<
          BaseMapApiDefinition,
          AxiosRequestConfig<AxiosResponse<BaseMapApiDefinition>>
        >(url);

        setData(result);

        if (params.callback) {
          params.callback({
            x: result.character_position.x_position,
            y: result.character_position.y_position,
          });
        }
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
    fetchCharacterMapDetails().catch(() => {});
  }, [fetchCharacterMapDetails]);

  return {
    data,
    error,
    loading,
  };
};

export default useBaseMapDetailsApi;
