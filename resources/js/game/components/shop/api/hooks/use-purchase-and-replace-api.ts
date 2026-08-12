import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

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

  // `params.on_success` is recreated by the caller on every render. Reading it
  // through a ref keeps `purchaseAndReplaceItem` stable across renders
  // (avoiding a re-purchase loop from the effect below) while still calling
  // the latest callback instead of a stale closure.
  const onSuccessRef = useRef(params.on_success);

  useEffect(() => {
    onSuccessRef.current = params.on_success;
  }, [params.on_success]);

  const purchaseAndReplaceItem = useCallback(async () => {
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

      onSuccessRef.current(result.message, {
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
  }, [apiHandler, url, requestParams]);

  useEffect(() => {
    if (params.character_id <= 0) {
      return;
    }

    purchaseAndReplaceItem().catch(() => {});
  }, [purchaseAndReplaceItem, requestParams, params.character_id]);

  return {
    error,
    loading,
    setRequestParams,
  };
};
