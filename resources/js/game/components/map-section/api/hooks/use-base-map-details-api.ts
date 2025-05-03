import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import BaseMapApiDefinition from './definitions/base-map-api-definition';
import BaseMapDetailsApiDefinition from './definitions/base-map-details-api-definition';
import UseBaseMapDetailsParams from './definitions/use-base-map-details-params';

const useBaseMapDetailsApi = (
  params: UseBaseMapDetailsParams
): BaseMapDetailsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<BaseMapApiDefinition | null>(null);
  const [error, setError] =
    useState<BaseMapDetailsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  if (!params.characterData) {
    setLoading(false);

    return {
      data,
      error,
      loading,
    };
  }

  const url = getUrl(params.url, { character: params.characterData.id });

  const fetchCharacterEquippedItems = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        BaseMapApiDefinition,
        AxiosRequestConfig<AxiosResponse<BaseMapApiDefinition>>
      >(url);

      setData(result);

      if (!params.callback) {
        return;
      }

      params.callback({
        x: result.character_position.x_position,
        y: result.character_position.y_position,
      });
    } catch (error) {
      if (error instanceof AxiosError) {
        setError(error.response?.data || null);
      } else {
        setError(null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url]);

  useEffect(() => {
    fetchCharacterEquippedItems().catch(console.error);
  }, [fetchCharacterEquippedItems]);

  return {
    data,
    error,
    loading,
  };
};

export default useBaseMapDetailsApi;
