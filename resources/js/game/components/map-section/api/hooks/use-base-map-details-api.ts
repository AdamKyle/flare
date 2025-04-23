import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import BaseMapApiDefinition from './definitions/base-map-api-definition';
import BaseMapDetailsApiDefinition from './definitions/base-map-details-api-definition';

const useBaseMapDetailsApi = (
  params: ApiParametersDefinitions
): BaseMapDetailsApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] = useState<BaseMapApiDefinition | null>(null);
  const [error, setError] =
    useState<BaseMapDetailsApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const fetchCharacterEquippedItems = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        BaseMapApiDefinition,
        AxiosRequestConfig<AxiosResponse<BaseMapApiDefinition>>
      >(url);

      setData(result);
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
