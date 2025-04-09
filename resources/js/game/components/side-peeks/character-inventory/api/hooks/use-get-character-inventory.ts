import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UseGetCharacterInventoryDefinition from './definitions/use-get-character-inventory-definition';
import UseGetCharacterInventoryState from './types/use-get-character-inventory-state';
import CharacterInventoryDefinition from '../../api-definitions/character-inventory-definition';

export const useGetCharacterInventory = (
  params: ApiParametersDefinitions
): UseGetCharacterInventoryDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] = useState<UseGetCharacterInventoryState['data']>(null);
  const [error, setError] =
    useState<UseGetCharacterInventoryState['error']>(null);
  const [loading, setLoading] =
    useState<UseGetCharacterInventoryState['loading']>(true);

  const fetchCharacterInventory = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        CharacterInventoryDefinition,
        AxiosRequestConfig<CharacterInventoryDefinition>
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
    fetchCharacterInventory().catch((error) => console.error(error));
  }, [fetchCharacterInventory]);

  return {
    data,
    error,
    loading,
  };
};
