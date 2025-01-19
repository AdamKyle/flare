import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseGetCharacterGemBagDefinition from './definitions/use-get-character-gem-bag-definition';
import UseGetCharacterGemBagState from './types/use-get-character-gem-bag-state';
import BaseGemDetails from '../../../../../../api-definitions/items/base-gem-details';

export const useGetCharacterGemBag = (
  params: ApiParametersDefinitions
): UseGetCharacterGemBagDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] = useState<UseGetCharacterGemBagState['data']>(null);
  const [error, setError] = useState<UseGetCharacterGemBagState['error']>(null);
  const [loading, setLoading] =
    useState<UseGetCharacterGemBagState['loading']>(true);

  const fetchCharacterGemBag = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        BaseGemDetails[],
        AxiosRequestConfig<BaseGemDetails[]>
      >(url);

      setData(result);
    } catch (error) {
      if (error instanceof AxiosError) {
        setError(error.response?.data);
      } else {
        throw error;
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, url]);

  useEffect(() => {
    fetchCharacterGemBag().catch((error) => console.error(error));
  }, [fetchCharacterGemBag]);

  return {
    data,
    error,
    loading,
  };
};
