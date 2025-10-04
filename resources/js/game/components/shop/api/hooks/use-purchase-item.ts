import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import UsePurchaseItemDefinition from './definitions/use-purchase-item-definition';
import UsePurchaseItemParams from './definitions/use-purchase-item-params';
import { ShopApiUrls } from '../enums/shop-api-urls';
import { PurchaseResponse } from './definitions/base-shop-purchase-response-definition';
import UsePurchaseItemRequestDefinition from './definitions/use-purchase-item-request-definition';

export const usePurchaseItem = (
  params: UsePurchaseItemParams
): UsePurchaseItemDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [error, setError] = useState<UsePurchaseItemDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestParams, setRequestParams] =
    useState<UsePurchaseItemRequestDefinition>({
      item_id: 0,
    });

  const url = getUrl(ShopApiUrls.BUY_ITEM, {
    character: params.character_id,
  });

  const purchaseItem = useCallback(
    async () => {
      setLoading(true);
      setError(null);
      setSuccessMessage(null);

      try {
        const result = await apiHandler.post<
          PurchaseResponse,
          AxiosRequestConfig<PurchaseResponse>,
          UsePurchaseItemRequestDefinition
        >(url, {
          item_id: requestParams.item_id,
        });

        setSuccessMessage(result.message);

        params.on_success({
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
    if (requestParams.item_id <= 0) {
      return;
    }

    purchaseItem().catch(() => {});
  }, [purchaseItem, requestParams]);

  return {
    successMessage,
    error,
    loading,
    setRequestParams,
  };
};
