import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import axios, { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import UseManageMultipleSelectedItemsApiParams from './deffinitions/use-manage-multiple-selected-items-api-params';
import UseManageMultipleSelectedItemsDefinition from './deffinitions/use-manage-multiple-selected-items-definition';
import UseManageMultipleSelectedItemsResponse from './deffinitions/use-manage-multiple-selected-items-response';
import { ItemSelectedType } from '../types/item-selected-type';

import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';

export const useManageMultipleSelectedItemsApi =
  (): UseManageMultipleSelectedItemsDefinition => {
    const { apiHandler, getUrl } = useApiHandler();

    const [loading, setLoading] = useState(false);
    const [error, setError] =
      useState<UseManageMultipleSelectedItemsDefinition['error']>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const abortControllerRef = useRef<AbortController | null>(null);

    useEffect(() => {
      return () => {
        const activeController = abortControllerRef.current;

        abortControllerRef.current = null;
        activeController?.abort();
      };
    }, []);

    const processApiCall = useCallback(
      async (
        url: string,
        params: ItemSelectedType,
        onSuccess: (inventory_count: InventoryCountDefinition) => void
      ): Promise<void> => {
        abortControllerRef.current?.abort();
        const controller = new AbortController();
        abortControllerRef.current = controller;

        setLoading(true);
        setError(null);
        setSuccessMessage(null);

        try {
          const result = await apiHandler.post<
            UseManageMultipleSelectedItemsResponse,
            AxiosRequestConfig<
              AxiosResponse<UseManageMultipleSelectedItemsResponse>
            >,
            ItemSelectedType
          >(
            url,
            {
              mode: params.mode,
              ids: params.ids,
              exclude: params.exclude,
            },
            { signal: controller.signal }
          );

          if (abortControllerRef.current !== controller) {
            return;
          }

          onSuccess(result.inventory_count);

          setSuccessMessage(result.message);
        } catch (err) {
          if (axios.isCancel(err)) {
            return;
          }

          if (abortControllerRef.current !== controller) {
            return;
          }

          if (err instanceof AxiosError && err.response?.data) {
            setError(err.response.data);

            return;
          }

          setError({ message: 'Unable to update the selected items.' });
        } finally {
          if (abortControllerRef.current === controller) {
            abortControllerRef.current = null;
            setLoading(false);
          }
        }
      },
      [apiHandler]
    );

    const handleSelection = useCallback(
      (params: UseManageMultipleSelectedItemsApiParams) => {
        const url = getUrl(params.url, { character: params.character_id });

        void processApiCall(url, params.apiParams, params.onSuccess);
      },
      [getUrl, processApiCall]
    );

    return {
      handleSelection,
      error,
      successMessage,
      loading,
    };
  };
