import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { resolveApiErrorMessage } from 'api-handler/utils/resolve-api-error-message';
import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UsePurchaseGoblinShopItemDefinition from './definitions/use-purchase-goblin-shop-item-definition';
import UsePurchaseGoblinShopItemParams from './definitions/use-purchase-goblin-shop-item-params';
import GoblinShopPurchaseRequestDefinition from '../definitions/goblin-shop-purchase-request-definition';
import GoblinShopPurchaseResponseDefinition from '../definitions/goblin-shop-purchase-response-definition';
import { GoblinShopApiUrls } from '../enums/goblin-shop-api-urls';

export const usePurchaseGoblinShopItem = ({
  character_id,
  item_id,
  on_success,
}: UsePurchaseGoblinShopItemParams): UsePurchaseGoblinShopItemDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();

  const [loading, setLoading] = useState(false);
  const [error, setError] =
    useState<UsePurchaseGoblinShopItemDefinition['error']>(null);

  const isSubmittingRef = useRef(false);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    return () => {
      const activeController = abortControllerRef.current;

      abortControllerRef.current = null;
      activeController?.abort();
    };
  }, []);

  const purchase = useCallback(
    async (
      amount: number
    ): Promise<GoblinShopPurchaseResponseDefinition | null> => {
      if (isSubmittingRef.current || character_id <= 0) {
        return null;
      }

      isSubmittingRef.current = true;
      const controller = new AbortController();
      abortControllerRef.current = controller;

      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          GoblinShopPurchaseResponseDefinition,
          AxiosRequestConfig<GoblinShopPurchaseResponseDefinition>,
          GoblinShopPurchaseRequestDefinition
        >(
          getUrl(GoblinShopApiUrls.BUY_ITEM, {
            character: character_id,
            item: item_id,
          }),
          { amount },
          { signal: controller.signal }
        );

        on_success(result);

        return result;
      } catch (requestError) {
        if (axios.isCancel(requestError)) {
          return null;
        }

        setError({
          message: resolveApiErrorMessage(
            requestError,
            'Unable to buy this item from the Goblin Shop.'
          ),
        });

        if (requestError instanceof AxiosError) {
          handleInactivity({ response: requestError, setError });
        }

        return null;
      } finally {
        isSubmittingRef.current = false;

        if (abortControllerRef.current === controller) {
          abortControllerRef.current = null;
          setLoading(false);
        }
      }
    },
    [apiHandler, getUrl, handleInactivity, character_id, item_id, on_success]
  );

  return { loading, error, purchase };
};
