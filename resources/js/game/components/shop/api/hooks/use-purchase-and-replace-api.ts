import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UsePurchaseAndReplaceApiDefinition from './definitions/use-purchase-and-replace-api-definition';
import UsePurchaseAndReplaceApiRequestDefinition from './definitions/use-purchase-and-replace-api-request-definition';
import { ShopApiUrls } from '../enums/shop-api-urls';
import UsePurchaseAndReplaceApiParams from './definitions/use-purchase-and-replace-api-params';
import UsePurchaseAndReplaceApiResponseDefinition from './definitions/use-purchase-and-replace-api-response-definition';

export const usePurchaseAndReplaceApi = (
  params: UsePurchaseAndReplaceApiParams
): UsePurchaseAndReplaceApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [error, setError] =
    useState<UsePurchaseAndReplaceApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestParams, setRequestParams] =
    useState<UsePurchaseAndReplaceApiRequestDefinition>({
      position: null,
      slot_id: 0,
      item_id_to_buy: 0,
      equip_type: null,
    });

  const url = getUrl(ShopApiUrls.BUY_AND_REPLACE, {
    character: params.character_id,
  });

  const purchaseAndReplaceItem = useCallback(
    async () => {
      if (requestParams.slot_id === 0) {
        return null;
      }

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          UsePurchaseAndReplaceApiResponseDefinition,
          AxiosRequestConfig<UsePurchaseAndReplaceApiResponseDefinition>,
          UsePurchaseAndReplaceApiRequestDefinition
        >(url, {
          item_id_to_buy: requestParams.item_id_to_buy,
          slot_id: requestParams.slot_id,
          position: requestParams.position,
          equip_type: requestParams.equip_type,
        });

        params.on_success(result.message, {
          gold: result.gold,
          inventory_count: result.inventory_count,
        });

        setLoading(false);
      } catch (err) {
        if (err instanceof AxiosError) {
          setError(err.response?.data || null);
        }
      } finally {
        setLoading(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url, requestParams]
  );

  useEffect(() => {
    if (params.character_id <= 0) {
      return;
    }

    purchaseAndReplaceItem().catch(() => {});

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [purchaseAndReplaceItem, requestParams]);

  return {
    error,
    loading,
    setRequestParams,
  };
};
