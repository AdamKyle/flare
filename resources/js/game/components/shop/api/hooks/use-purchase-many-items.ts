import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { ShopApiUrls } from '../enums/shop-api-urls';
import { PurchaseResponse } from './definitions/base-shop-purchase-response-definition';
import UsePurchaseManyItemsDefinition from './definitions/use-purchase-many-items-definition';
import UsePurchaseManyItemsParams from './definitions/use-purchase-many-items-params';
import UsePurchaseManyItemsRequestDefinition from './definitions/use-purchase-many-items-request-definition';

export const UsePurchaseManyItems = (
  params: UsePurchaseManyItemsParams
): UsePurchaseManyItemsDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [error, setError] =
    useState<UsePurchaseManyItemsDefinition['error']>(null);
  const [loading, setLoading] = useState(false);
  const [requestParams, setRequestParams] =
    useState<UsePurchaseManyItemsRequestDefinition>({
      item_id: 0,
      amount: 0,
    });

  const url = getUrl(ShopApiUrls.BUY_MULTIPLE, {
    character: params.character_id,
  });

  const purchaseManyItems = useCallback(
    async () => {
      setLoading(true);
      setError(null);
      setSuccessMessage(null);

      try {
        const result = await apiHandler.post<
          PurchaseResponse,
          AxiosRequestConfig<PurchaseResponse>,
          UsePurchaseManyItemsRequestDefinition
        >(url, {
          item_id: requestParams.item_id,
          amount: requestParams.amount,
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

    purchaseManyItems().catch(() => {});
  }, [purchaseManyItems, requestParams]);

  return {
    successMessage,
    error,
    loading,
    setRequestParams,
  };
};
