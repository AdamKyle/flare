import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useState } from 'react';

import UseManageMultipleSelectedItemsApiParams from './deffinitions/use-manage-multiple-selected-items-api-params';
import UseManageMultipleSelectedItemsDefinition from './deffinitions/use-manage-multiple-selected-items-definition';
import UseManageMultipleSelectedItemsResponse from './deffinitions/use-manage-multiple-selected-items-response';

export const useManageMultipleSelectedItemsApi =
  (): UseManageMultipleSelectedItemsDefinition => {
    const { apiHandler, getUrl } = useApiHandler();

    const [loading, setLoading] = useState(false);
    const [error, setError] =
      useState<UseManageMultipleSelectedItemsDefinition['error']>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const handleSelection = (
      params: UseManageMultipleSelectedItemsApiParams
    ) => {
      const url = getUrl(params.url, { character: params.character_id });

      processApiCall(url, params.apiParams, params.onSuccess).catch(() => {});
    };

    const processApiCall = async (
      url: string,
      params: ItemSelectedType,
      onSuccess: () => void
    ) => {
      setLoading(true);

      try {
        const result = await apiHandler.post<
          UseManageMultipleSelectedItemsResponse,
          AxiosRequestConfig<
            AxiosResponse<UseManageMultipleSelectedItemsResponse>
          >,
          ItemSelectedType
        >(url, {
          mode: params.mode,
          ids: params.ids,
          exclude: params.exclude,
        });

        onSuccess();

        setSuccessMessage(result.message);

        setLoading(false);
      } catch (err) {
        setLoading(false);

        if (err instanceof AxiosError) {
          setError(err.response?.data);
        }
      }
    };

    return {
      handleSelection,
      error,
      successMessage,
      loading,
    };
  };
