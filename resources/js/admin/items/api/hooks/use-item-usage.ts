import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseItemUsageDefinition from './definitions/use-item-usage-definition';
import ItemUsageDefinition from '../definitions/item-usage-definition';
import { ItemApiMessages } from '../enums/item-api-messages';
import { ItemApiUrls } from '../enums/item-api-urls';

export const useItemUsage = (itemId: number): UseItemUsageDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [usage, setUsage] = useState<ItemUsageDefinition | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  const fetchUsage = useCallback(async () => {
    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const result = await apiHandler.get<
        ItemUsageDefinition,
        Record<string, never>
      >(getUrl(ItemApiUrls.USAGE, { item: itemId }), {
        signal: controller.signal,
      });

      if (requestGenerationRef.current !== requestGeneration) {
        return;
      }

      setUsage(result);
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

      setError({ message: ItemApiMessages.LoadUsage });
    } finally {
      if (requestGenerationRef.current === requestGeneration) {
        setLoading(false);
      }
    }
  }, [apiHandler, getUrl, itemId]);

  useEffect(() => {
    void fetchUsage();

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [fetchUsage, refreshToken]);

  const refresh = (): void => {
    setRefreshToken((value) => value + 1);
  };

  return {
    usage,
    loading,
    error,
    refresh,
  };
};
