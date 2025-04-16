import ApiParametersDefinitions from 'api-handler/definitions/api-parameters-definitions';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import {AxiosError, AxiosRequestConfig, AxiosResponse} from 'axios';
import { useCallback, useEffect, useState } from 'react';

import CharacterInventoryItemDetails from "./definitions/character-equipped-items-api-definitions";
import UseCharacterEquippedApiDefinition from "./types/use-character-equipped-api-definition";
import BaseInventoryItemDefinition
  from "../../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition";

const useCharacterEquippedItemsApi = (
  params: ApiParametersDefinitions,
): UseCharacterEquippedApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const url = getUrl(params.url, params.urlParams);

  const [data, setData] = useState<BaseInventoryItemDefinition[]>([]);
  const [error, setError] =
    useState<UseCharacterEquippedApiDefinition['error']>(null);
  const [loading, setLoading] = useState(true);

  const fetchCharacterEquippedItems = useCallback(async () => {

    try {
      const result = await apiHandler.get<
        CharacterInventoryItemDetails,
        AxiosRequestConfig<AxiosResponse<CharacterInventoryItemDetails>>
      >(url);

      setData(result.equipped.data);
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

export default useCharacterEquippedItemsApi;
