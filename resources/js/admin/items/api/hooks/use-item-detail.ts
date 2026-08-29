import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';

import UseItemDetailDefinition from './definitions/use-item-detail-definition';
import ItemDetailDefinition from '../definitions/item-detail-definition';
import { ItemApiMessages } from '../enums/item-api-messages';
import { ItemApiUrls } from '../enums/item-api-urls';

export const useItemDetail = (itemId: number): UseItemDetailDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [item, setItem] = useState<ItemDetailDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchItem = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        ItemDetailDefinition,
        Record<string, never>
      >(getUrl(ItemApiUrls.SHOW, { item: itemId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setItem(result);
    } catch (errorInstance) {
      if (axios.isCancel(errorInstance)) {
        return;
      }

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      if (axios.isAxiosError<{ message?: string }>(errorInstance)) {
        setError({
          message:
            errorInstance.response?.data?.message ?? errorInstance.message,
        });

        return;
      }

      setError({ message: ItemApiMessages.Load });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, itemId]);

  useEffect(() => {
    void fetchItem();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchItem, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return {
    item,
    loading,
    error,
    refresh,
  };
};
