import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { EquippableItemWithBase } from '../../../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import ItemDetails from '../../../../../../api-definitions/items/item-details';
import UseGetInventoryItemDetailsApiDefinition from '../definitions/use-get-inventory-item-details-api-request-definition';
import UseGetInventoryItemDetailsResponse from '../definitions/use-get-inventory-item-details-response-definition';

export const useGetInventoryItemDetails = ({
  character_id,
  item_id,
  url,
}: UseGetInventoryItemDetailsApiDefinition): UseGetInventoryItemDetailsResponse => {
  const { apiHandler, getUrl } = useApiHandler();

  const [data, setData] = useState<EquippableItemWithBase | null>(null);
  const [error, setError] =
    useState<UseGetInventoryItemDetailsResponse['error']>(null);
  const [loading, setLoading] = useState<boolean>(true);

  const apiUrl = getUrl(url, { character: character_id, item: item_id });

  const fetchInventoryItemDetails = useCallback(async () => {
    try {
      const result = await apiHandler.get<
        EquippableItemWithBase,
        AxiosRequestConfig<AxiosResponse<ItemDetails>>
      >(apiUrl);

      setData(result);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(err.response?.data || null);
      }
    } finally {
      setLoading(false);
    }
  }, [apiHandler, apiUrl]);

  useEffect(() => {
    fetchInventoryItemDetails().catch(() => {});
  }, [fetchInventoryItemDetails]);

  return {
    data,
    error,
    loading,
  };
};
