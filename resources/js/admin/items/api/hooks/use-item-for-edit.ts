import { AxiosErrorDefinition } from 'api-handler/definitions/axios-error-definition';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';

import UseItemForEditDefinition from './definitions/use-item-for-edit-definition';
import ItemFormDefinition from '../definitions/item-form-definition';
import { ItemApiMessages } from '../enums/item-api-messages';
import { ItemApiUrls } from '../enums/item-api-urls';

export const useItemForEdit = (
  itemId: number | null
): UseItemForEditDefinition => {
  const { apiHandler, getUrl } = useApiHandler();

  const [item, setItem] = useState<ItemFormDefinition | null>(null);
  const [loading, setLoading] = useState(itemId !== null);
  const [error, setError] = useState<AxiosErrorDefinition | null>(null);

  const requestGenerationRef = useRef(0);
  const abortControllerRef = useRef<AbortController | null>(null);

  useEffect(() => {
    if (itemId === null) {
      setLoading(false);

      return;
    }

    requestGenerationRef.current += 1;
    const requestGeneration = requestGenerationRef.current;

    abortControllerRef.current?.abort();
    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    const fetchItem = async () => {
      try {
        const result = await apiHandler.get<
          ItemFormDefinition,
          Record<string, never>
        >(getUrl(ItemApiUrls.EDIT, { item: itemId }), {
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
    };

    void fetchItem();

    return () => {
      controller.abort();
    };
  }, [apiHandler, getUrl, itemId]);

  return { item, loading, error };
};
